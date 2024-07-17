<?php
namespace OCA\PdfSignature\Service;

use OCA\PdfSignature\AppInfo\Application;
use OCP\IConfig;
use OCP\IUserSession;

use OCP\Files\IRootFolder;
use OCP\Files\File;

use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

use OCP\IL10N;
use Psr\Log\LoggerInterface;
use Throwable;

use OCA\PdfSignature\Service\PdfService;


class SignatureService {

    private IConfig $config;
    private IClient $client;
    private IUserSession $userSession;
    private IRootFolder $rootFolder;
    private LoggerInterface $logger;
    private IL10N $l10n;
    private PdfService $pdfService;

    public function __construct(
                                IConfig $config,
                                IClientService  $clientService,
                                IUserSession $userSession,
                                IRootFolder $rootFolder,
                                LoggerInterface $logger,
                                IL10N $l10n,
                                PdfService $pdfService)
    {
                            
        $this->config = $config;
        $this->userSession = $userSession;
        $this->rootFolder = $rootFolder;
        $this->client = $clientService->newClient();
        $this->logger = $logger;
		$this->l10n = $l10n;
        $this->pdfService = $pdfService;
    }

    public function sendSignatureRequest($all_params): array
    {
        $doc_data = $this->getDocumentData($all_params["document"],$all_params["path"]);

        $new_name = str_replace(".pdf","-signed.pdf",$doc_data["name"]);

        $p12_content = $this->getEncodedData($all_params["pkcs12"]);

        $pkcs12 = array("content" => $p12_content);
        $pkcs12_password = array("content" => base64_encode($all_params["pkcs12_password"]));
        $document = array("content" => base64_encode($doc_data["content"]));

        $logo_position = $this->pdfService->getPdfCoords($all_params["logo_position"]);

        // Todo: Do this by mean job
        $this->pdfService->cleanTempData();
        // Todo: Do this by mean job

        $pdf_page = $all_params["pdf_page"] === "fp" ? 0 : -1;

        $image_path = $all_params["logo_image"];

        $logo_image = null;
        
        if (isset($image_path))
        {
            if(\str_contains($image_path,"base64,"))
            {
                $logo_image = array("content" => $this->getEncodedData($image_path));
            }
            else
            {
                $imageData = $this->getImageData($image_path);
                if(isset($imageData))
                {
                    $logo_image = array("content" => $imageData);
                }
            }

        }        
        $params = array("user_id" => $doc_data["user_id"], "pkcs12" => $pkcs12, 
                        "pkcs12_password" => $pkcs12_password,
                        "document"=> $document,
                        "image" => $logo_image,
                        "coords" =>$logo_position,
                        "page" => $pdf_page);

        $request_signature = $this->makeRequest("sign_doc",$params,"POST");
        if(isset($request_signature["error"]))
        {
            return [
                "error" => $request_signature["error"],
                "code" => $request_signature["code"]
            ];
        }
        if(isset($request_signature["code"]) && $request_signature["code"] == 200)
        {
            $doc_data["user_folder"]->newFile($new_name, \base64_decode($request_signature['content']));
        }

        return $request_signature;
    }

    public function sendValidateRequest($all_params):array
    {
        $doc_data = $this->getDocumentData($all_params["document"],$all_params["path"]);

        $document = array("content" => base64_encode($doc_data["content"]));
        $params = array("user_id"=>$doc_data["user_id"],"document"=>$document);

        $request_validate = $this->makeRequest("validate_doc",$params,"POST");
        if(isset($request_validate["error"]))
        {
            return [
                "error" => $request_validate["error"],
                "code" => $request_validate["code"]
            ];
        }
        $response = [];
        
        if(count($request_validate["content"]) == 0) 
        {
            return ["ns" => $this->l10n->t("This document don't have signatures")];
        }
        else
        {
            
            foreach($request_validate["content"] as $index => $sig_info)
            {
                $response[$index]["signer"] = $this->getSignerName($sig_info[0]);
                $response[$index]["crt"] = "Certificate status is ".$sig_info[1];
                $response[$index]["integrity"] = $this->getSignatureIntegrity($sig_info[0]);
                $response[$index]["time"] = $this->formatDate($this->getSignatureTimeInfo($sig_info[0]));
                $response[$index]["modification"] = $this->getSignatureModification($sig_info[0]);
                $response[$index]["sumary"] = $sig_info[1] == "revoked" ? "Signature is invalid by certificate revocation" : $this->getSignatureSumary($sig_info[0]);
            }
            return $response;
        }

    }

    public function sendPdfPageRequest($all_params) : string {
        $doc_data = $this->getDocumentData($all_params["document"],$all_params["path"]);

        file_put_contents($this->pdfService->getUniquePdfPath(), $doc_data["content"]);

        $this->pdfService->initPdfInfo();

        $pdf_page_image = $this->pdfService->getPdfPageImage($all_params["pdf_page"]);

        if(!empty($pdf_page_image))
        {
            return $pdf_page_image;
        }
        else
        {
            throw new \Exception("Unable to get pdf page");
        }

    }

    public function makeRequest(string $endPoint, array $params = [], string $method = 'GET'): array {
        try{
            $apiHost = $this->config->getAppValue(Application::APP_ID, 'api_host');
            $apiKey = $this->config->getAppValue(Application::APP_ID, 'api_key');
            
            if(empty($apiHost) || empty($apiKey)){
                $this->logger->error("Mising API configuration", ['app' => Application::APP_ID]);
                return [
                    "error" => "Mising API configuration",
                    "code" => 500
                ];
            }
    
            $url_request = $apiHost."$endPoint?api_key=$apiKey"; 
    
            $options = [
                'headers' => [
                    'accept' => 'application/json',
                    'Content-Type'=> 'application/json',
                ],
                'nextcloud' => [
                    'allow_local_address' => true,
                ],
                'verify' => false
            ];
    
            if (count($params) > 0) {
                if ($method === 'GET') {
                    $paramsContent = http_build_query($params);
                    $url_request .= '?' . $paramsContent;
                } else {
                    $options['body'] = json_encode($params);
                }
            }
    
            if ($method === 'GET') {
                $response = $this->client->get($url_request, $options);
            } else if ($method === 'POST') {
                $response = $this->client->post($url_request, $options);
            } else {
                return ['error' => $this->l10n->t('Bad HTTP method'),'code' => 501];
            }
    
            $body = $response->getBody();
            $respCode = $response->getStatusCode();
    
            return ['content' => json_decode($body)->content, 'code' => $respCode];

        }catch(ClientException | ServerException $e){

            $responseBody = $e->getResponse()->getBody();
			$parsedResponseBody = json_decode($responseBody, true);
			if ($e->getResponse()->getStatusCode() === 404) {
				$this->logger->error('Signature API error : ' . $e->getMessage(), ['response_body' => $responseBody, 'app' => Application::APP_ID]);
			} else {
				$this->logger->error('Signature API error : ' . $e->getMessage(), ['response_body' => $responseBody, 'app' => Application::APP_ID]);
			}
			return [
				'error' => $e->getMessage(),
				'code' => $e->getResponse()->getStatusCode(),
			];
        }
        catch (Exception | Throwable $e) {
			$this->logger->error('Signature API error : ' . $e->getMessage(), ['app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}


    }

    private function getImageData($path){
        $user_id = $this->userSession->getUser()->getUID();
        $user_folder_path = $this->rootFolder->getUserFolder($user_id);

        if($user_folder_path->nodeExists($path))
        {
            $file = $user_folder_path->get($path);
            if ($file instanceof File) {
                return base64_encode($file->getContent());
            }
        }
        return null;
    }
    private function getDocumentData($document,$path):array
    {
        $doc_path = $path != "/" ? $path."/" : "";
        $name = $doc_path.$document;
        $user_id = $this->userSession->getUser()->getUID();
        $user_folder_path = $this->rootFolder->getUserFolder($user_id);

        if($user_folder_path->nodeExists($name))
        {
            $file = $user_folder_path->get($name);
            if ($file instanceof File) {
                $content_pdf = $file->getContent();
            }
            else {
                return [
                    "error" => "Mising file",
                    "code" => 500
                ];
            }
        }
        else{
            return [
                "error" => "Mising file",
                "code" => 500
            ]; 
        }

        return ["user_id"=>$user_id,"name"=>$name,"user_folder"=> $user_folder_path,"content"=>$content_pdf];
    }

    private function getSignerName($content):string
    {
        $cn_pattern = "/Common Name:\s+([a-zA-Z áéíóúÁÉÍÓÚñÑ]+\s*)+/";
        preg_match_all($cn_pattern,$content,$matches);
        $signer_cn_str = $matches[0][0];
        $str_temp = strstr($signer_cn_str,":");

        return substr($str_temp,2);
    }


    private function getSignatureIntegrity($content): string
    {
        $integrity_pattern = "/Integrity\s+---------\s+([A-z|a-z]+\s*)+/";
        preg_match($integrity_pattern,$content,$matches);
        $str_temp = strstr($matches[0],"---------");
        return substr($str_temp,10);
    }

    private function getSignatureTimeInfo($content): string
    {
        $time_pattern = "/Signing time\s+------------\s+([A-z|a-z]+\s*)+:\s+([0-9]{2,4}-)+[0-9|A-Z]+:[0-9]{2}:([0-9]{2}\+[0-9]{2}):[0-9]+/";
        preg_match($time_pattern,$content,$matches);
        $str_temp = strstr($matches[0],":");
        return substr($str_temp,2);
    }

    private function getSignatureModification($content): string
    {
        $time_pattern = "/Modifications\s+-------------\s+([A-z|a-z]+\s*)+/";
        preg_match($time_pattern,$content,$matches);
        $str_temp = strstr($matches[0],"-------------");
        return substr($str_temp,14);
    }

    private function getSignatureSumary($content): string
    {
        $time_pattern = "/Bottom line\s+-----------\s+([A-z|a-z]+\s*)+/";
        preg_match($time_pattern,$content,$matches);
        $str_temp = strstr($matches[0],"-----------");
        return substr($str_temp,12);
    }
    private function formatDate($date): string
    {
        $data_date = new \DateTime($date);
        $data_date->setTimezone(new \DateTimeZone('America/Havana'));
        return $data_date->format("D, M j 'Y - h:i A");
    }

    private function getEncodedData($data): string
    {
        $pos_header_base64 = strpos($data,"base64,");

        return \substr($data,$pos_header_base64 + 7);
    }
}