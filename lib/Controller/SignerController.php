<?php
namespace OCA\PdfSignature\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;

use OCP\IRequest;
use OCA\PdfSignature\Service\SignatureService;


class SignerController extends Controller {
    
    private SignatureService $service;

    public function __construct(string        $appName,
                                IRequest      $request,
                                SignatureService $service)
    {
        parent::__construct($appName, $request);
        $this->service = $service;
    }

    #[NoAdminRequired]
    public function requestSignature(): DataResponse
    {
        $all_params = $this->request->getParams();
        $response = $this->service->sendSignatureRequest($all_params);
        return $response['code'] == 200 ? new DataResponse("ok"): new DataResponse($response);
    }

    #[NoAdminRequired]
    public function requestValidateSignature(): DataResponse
    {
        $all_params = $this->request->getParams();
        $response = $this->service->sendValidateRequest($all_params);
        return new DataResponse($response);
    }

    #[NoAdminRequired]
    public function requestPdfPage(): DataResponse
    {
        $all_params = $this->request->getParams();
        $response = $this->service->sendPdfPageRequest($all_params);
        return new DataResponse($response);
    }
}