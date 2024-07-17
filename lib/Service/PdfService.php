<?php
namespace OCA\PdfSignature\Service;

use OCP\IUserSession;
use OCP\App\IAppManager;

use NcJoes\PopplerPhp\PdfInfo;
use NcJoes\PopplerPhp\Config;
use NcJoes\PopplerPhp\PdfToCairo;

class PdfService {

    private IUserSession $userSession;
    private IAppManager $appManager;
    private $pdfInfo = array();

    public function __construct(

        IUserSession $userSession,
        IAppManager $appManager)
    {
        $this->userSession = $userSession;
        $this->appManager = $appManager;
        Config::setOutputDirectory($this->getTempDir());
    }

    public function initPdfInfo():void 
    {
        $pdf = new PdfInfo($this->getUniquePdfPath());
        $this->pdfInfo = $pdf->getInfo();
    }

    public function cleanTempData(): void
    {
        if(file_exists($this->getUniquePdfPath()))
        {
            unlink($this->getUniquePdfPath());
        }
        if(file_exists($this->getUniquePdfPath('jpg')))
        {
            unlink($this->getUniquePdfPath('jpg'));
        }
        
    }

    public function getPdfInfo(): array
    {
        return $this->pdfInfo;
    }

    public function getTempDir(): string
    {
        $appBasePath = $this->appManager->getAppPath('pdfsignature');
        return "$appBasePath/lib/Resources/pdf/";
    }

    public function getUniquePdfPath($ext = 'pdf'): string
    {
        $user_id = $this->userSession->getUser()->getUID();
        return $this->getTempDir().$user_id.".$ext";
    }


    public function getPdfPageImage($page): string
    {
        $cairoFormat = new PdfToCairo($this->getUniquePdfPath());  
        $pages = intval($this->pdfInfo["pages"]); 

        if($page == 'fp' || $page == null || $pages == 1)
        {
            $cairoFormat->firstPageOnly();
            $cairoFormat->generateJPG();
        }
        else
        {
            $tempDir = $this->getTempDir();
            $suffix = "-$pages.jpg";
            $user_id = $this->userSession->getUser()->getUID();

            $cairoFormat->startFromPage($pages);
            $cairoFormat->generateJPG(); 
            rename($tempDir.$user_id.$suffix,$tempDir.$user_id.'.jpg');
        }

        $page_image = file_get_contents($this->getUniquePdfPath('jpg'));
        return 'data:image/jpeg;base64,'.base64_encode($page_image);
    }

    public function getPdfCoords($visorCoords): array
    {
        $this->initPdfInfo();
        $page_size = $this->pdfInfo["page_size"];
        $pattern = "/[0-9]+(\.[0-9]+)?/";
        preg_match_all($pattern,$page_size,$matches);

        $page_w_value = floatval($matches[0][0]);
        $page_h_value = floatval($matches[0][1]);

        $point_coords = explode(',', $visorCoords);
        $x_coord = intval($point_coords[0]);
        $y_coord = intval($point_coords[1]);

        $x1 = round(($x_coord *  $page_w_value)/100);
        $y2 = round(($y_coord *  $page_h_value)/100);

        $x2 = $x1 + 160;
        $y1 = $y2 - 70;

        return [$x1, $y1, $x2, $y2];
    }
}