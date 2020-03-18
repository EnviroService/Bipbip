<?php

namespace App\Controller;

use App\Entity\Estimations;
use App\Entity\User;
use App\Repository\EstimationsRepository;
use App\Repository\UserRepository;
use SoapClient;
use SoapFault;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    /**
     * @Route("/mode-envoi/{id}", name="mode_envoi")
     * @param Estimations $estimation
     * @return RedirectResponse|Response
     */
    public function choiceTransport(Estimations $estimation)
    {
        if ($this->getUser()->getRoles()[0] === "ROLE_ADMIN" || $this->getUser()->getRoles()[0] === "ROLE_COLLECTOR") {
            return $this->redirectToRoute('estimations_index');
        } else {
            $user = $this->getUser();


            if ($user == null) {
                $this->addFlash(
                    "error",
                    "Tu as été déconnecté pour le bien de la planéte... Connecte-toi à nouveau ..."
                );
                return $this->redirectToRoute('app_login');
            } else {
                return $this->render("user/choiceEnvoi.html.twig", [
                    'user' => $user,
                    'estimation' => $estimation
                ]);
            }
        }
    }

    /**
     * @Route("/envoi-chronopost", name="envoi_chronopost")
     */
    public function envoiChronopost()
    {
        $user = $this->getUser();
        $estimation = $_GET['id'];
        if ($user == null) {
            $this->addFlash("error", "Tu as été déconnecté pour le bien de la planéte... Connecte-toi 
            à nouveau ...");
            $this->redirectToRoute('app_login');
        }

        return $this->render("user/envoi_chronopost.html.twig", [
            'user' => $user,
            'estimation' => $estimation
        ]);
    }

    /**
     * @Route("/chronopost/{id}", name="api_chronopost_ae")
     * @param User $user
     * @return Response
     * @throws SoapFault
     */
    public function apiChronopostAe(User $user, EstimationsRepository $repository)
    {

        if ($this->getUser()->getId() == $user->getId()) {
            $wsdl = "https://ws.chronopost.fr/shipping-cxf/ShippingServiceWS?wsdl";
            $clientCh = new SoapClient($wsdl);
            //$clientCh->soap_defencoding = 'UTF-8';
            //$clientCh->decode_utf8 = false;

            $firstname = $user->getFirstname();
            $name = $user->getLastname();

            $params = [
                //STRUCTURE HEADER VALUE
                'headerValue' => [
                    'accountNumber' => '19869502',
                    'idEmit' => 'CHRFR',
                    'identWebPro' => '',
                    'subAccount' => '',
                ],
                //STRUCTURE SHIPPERVALUE (expediteur)
                'shipperValue' => [
                    'shipperAdress1' => $user->getAddress(),
                    'shipperAdress2' => '',
                    'shipperCity' => $user->getCity(),
                    'shipperCivility' => 'M',
                    'shipperContactName' => "$name $firstname",
                    'shipperCountry' => 'FR',
                    'shipperCountryName' => 'FRANCE',
                    'shipperEmail' => $user->getEmail(),
                    'shipperMobilePhone' => $user->getPhoneNumber(),
                    'shipperName' => $firstname,
                    'shipperName2' => $name,
                    'shipperPhone' => $user->getPhoneNumber(),
                    'shipperPreAlert' => 0,
                    'shipperZipCode' => $user->getPostCode(),
                ],
                //STRUCTURE CUSTOMERVALUE
                'customerValue' => [
                    'customerAdress1' => '40 RUE JEAN JAURES',
                    'customerAdress2' => '',
                    'customerCity' => 'MONFRIN',
                    'customerCivility' => 'M',
                    'customerContactName' => 'Jean MARTIN',
                    'customerCountry' => 'FR',
                    'customerCountryName' => 'FRANCE',
                    'customerEmail' => 'jerem62026@gmail.com',
                    'customerMobilePhone' => '0611223344',
                    'customerName' => 'The Journal',
                    'customerName2' => '',
                    'customerPhone' => '0133333333',
                    'customerPreAlert' => 0,
                    'customerZipCode' => '72000',
                    'printAsSender' => 'N',
                ],
                //STRUCTURE RECIPIENTVALUE (destinataire)
                'recipientValue' => [
                    'recipientAdress1' => '391 avenue Clément Ader',
                    'recipientAdress2' => '',
                    'recipientCity' => 'Wambrechies',
                    'recipientContactName' => 'Natacha',
                    'recipientCountry' => 'FR',
                    'recipientCountryName' => 'FRANCE',
                    'recipientEmail' => 'test@gmail.com',
                    'recipientMobilePhone' => '0655667788',
                    'recipientName' => 'BipBip Mobile',
                    'recipientName2' => '',
                    'recipientPhone' => '0455667788',
                    'recipientPreAlert' => 0,
                    'recipientZipCode' => '59118',
                    'recipientCivility' => 'M',
                ],
                //STRUCTURE REFVALUE
                'refValue' => [
                    'customerSkybillNumber' => '123456789',
                    'PCardTransactionNumber' => '',
                    'recipientRef' => 24,
                    'shipperRef' => 000000000000001,
                ],
                //STRUCTURE SKYBILLVALUE
                'skybillValue' => [
                    'bulkNumber' => 1,
                    'codCurrency' => 'EUR',
                    'codValue' => 0,
                    'customsCurrency' => 'EUR',
                    'customsValue' => 0,
                    'evtCode' => 'DC',
                    'insuredCurrency' => 'EUR',
                    'insuredValue' => 0,
                    'masterSkybillNumber' => '?',
                    'objectType' => 'MAR',
                    'portCurrency' => 'EUR',
                    'portValue' => 0,
                    'productCode' => '01',
                    'service' => '0',
                    'shipDate' => date('c'),
                    'shipHour' => date('G'),
                    'skybillRank' => 1,
                    'weight' => 2,
                    'weightUnit' => 'KGM',
                    'height' => '10',
                    'length' => '30',
                    'width' => '40',
                ],
                //STRUCTURE SKYBILLPARAMSVALUE
                'skybillParamsValue' => [
                    'mode' => 'PPR',
                    'withReservation' => 0,
                ],
                //OTHERS
                'password' => '255562',
                'modeRetour' => '1',
                'numberOfParcel' => 1,
                'version' => '',
                'multiparcel' => 'N'
            ];

            // YOU CAN FIND PARAMETERS YOU NEED IN HERE
            //var_dump($client_ch->__getFunctions());
            //var_dump($client_ch->__getTypes());

            try {
                //Objet StdClass

                // demande la réponse de la méthode shippingMultiParcelV2
                $results = $clientCh->shippingMultiParcelV2($params);

                //récupération de l'étiquette en base64
                $pdf = $results->return->resultMultiParcelValue->pdfEtiquette;

                // Création d'un nom de fichier pour la sauvegarde.
                $idUser = $user->getId();

                // Récupération de l'id de l'estimation passée en GET
                $estimation = $_GET['estimation'];
                $date = date("d_M_Y");
                $repertory = "uploads/etiquette/";
                $filenameSave = $repertory . "id" . $idUser . "_" . $date . "_E" . $estimation . ".pdf";
                $filename = "id" . $idUser . "_" . $date . "_E" . $estimation . ".pdf";

                $openDir = scandir($repertory);
                foreach ($openDir as $value) {
                    if ($filename === $value) {
                        $this->addFlash('danger', 'Votre étiquette a déjà été enregistrée, 
                        elle est disponible sur votre profil');
                        return $this->redirectToRoute('user_show', [
                            'id' => $idUser
                        ]);
                    }
                }
                $fichier = fopen($filenameSave, "w");
                fwrite($fichier, $pdf);
                fclose($fichier);
                //$set_estimation = $repository->find($estimation);
                //$set_estimation->setStatus(1);

                return new Response($pdf, 200, [
                    'Content-Disposition' => "attachment; filename=$filename"
                ]);
            } catch (SoapFault $soapFault) {
                //var_dump($soapFault);
                echo "Request :<br>", htmlentities($clientCh->__getLastRequest()), "<br>";
                echo "Response :<br>", htmlentities($clientCh->__getLastResponse()), "<br>";
            }
        } else {
            $this->addFlash('danger', 'Vous ne pouvez pas éditer cette étiquette. Seules 
            les étiquettes vous appartenant sont disponibles');
            $id = $this->getUser()->getId();

            return $this->redirectToRoute("user_show", [
                'id' => $id
            ]);
        }
        $id = $this->getUser()->getId();

        return $this->redirectToRoute("user_show", [
            'id' => $id
        ]);
    }

    /**
     * @Route("/chronopost/{id}", name="api_chronopost_se")
     * @return Response
     * @throws SoapFault
     */
    public function apiChronopostSe(User $user)
    {

        $wsdl = "https://ws.chronopost.fr/shipping-cxf/ShippingServiceWS?wsdl";
        $clientCh = new SoapClient($wsdl);
        //$clientCh->soap_defencoding = 'UTF-8';
        //$clientCh->decode_utf8 = false;

        $firstname = $user->getFirstname();
        $name = $user->getLastname();

        $params = [
            //STRUCTURE HEADER VALUE
            'headerValue' => [
                'accountNumber' => '19869502',
                'idEmit' => 'CHRFR',
                'identWebPro' => '',
                'subAccount' => '',
            ],
            //STRUCTURE SHIPPERVALUE (expediteur)
            'shipperValue' => [
                'shipperAdress1' => $user->getAddress(),
                'shipperAdress2' => '',
                'shipperCity' => $user->getCity(),
                'shipperCivility' => 'M',
                'shipperContactName' => "$name $firstname",
                'shipperCountry' => 'FR',
                'shipperCountryName' => 'FRANCE',
                'shipperEmail' => $user->getEmail(),
                'shipperMobilePhone' => "0788232290",
                'shipperName' => $firstname,
                'shipperName2' => $name,
                'shipperPhone' => "0788232290",
                'shipperPreAlert' => 0,
                'shipperZipCode' => $user->getPostCode(),
            ],
            //STRUCTURE CUSTOMERVALUE
            'customerValue' => [
                'customerAdress1' => '40 RUE JEAN JAURES',
                'customerAdress2' => '',
                'customerCity' => 'MONFRIN',
                'customerCivility' => 'M',
                'customerContactName' => 'Jean MARTIN',
                'customerCountry' => 'FR',
                'customerCountryName' => 'FRANCE',
                'customerEmail' => 'jerem62026@gmail.com',
                'customerMobilePhone' => '0611223344',
                'customerName' => 'The Journal',
                'customerName2' => '',
                'customerPhone' => '0133333333',
                'customerPreAlert' => 0,
                'customerZipCode' => '72000',
                'printAsSender' => 'N',
            ],
            //STRUCTURE RECIPIENTVALUE (destinataire)
            'recipientValue' => [
                'recipientAdress1' => '391 avenue Clément Ader',
                'recipientAdress2' => '',
                'recipientCity' => 'Wambrechies',
                'recipientContactName' => 'Natacha',
                'recipientCountry' => 'FR',
                'recipientCountryName' => 'FRANCE',
                'recipientEmail' => 'test@gmail.com',
                'recipientMobilePhone' => '0655667788',
                'recipientName' => 'BipBip Mobile',
                'recipientName2' => '',
                'recipientPhone' => '0455667788',
                'recipientPreAlert' => 0,
                'recipientZipCode' => '59118',
                'recipientCivility' => 'M',
            ],
            //STRUCTURE REFVALUE
            'refValue' => [
                'customerSkybillNumber' => '123456789',
                'PCardTransactionNumber' => '',
                'recipientRef' => 24,
                'shipperRef' => 000000000000001,
            ],
            //STRUCTURE SKYBILLVALUE
            'skybillValue' => [
                'bulkNumber' => 1,
                'codCurrency' => 'EUR',
                'codValue' => 0,
                'customsCurrency' => 'EUR',
                'customsValue' => 0,
                'evtCode' => 'DC',
                'insuredCurrency' => 'EUR',
                'insuredValue' => 0,
                'masterSkybillNumber' => '?',
                'objectType' => 'MAR',
                'portCurrency' => 'EUR',
                'portValue' => 0,
                'productCode' => '01',
                'service' => '0',
                'shipDate' => date('c'),
                'shipHour' => date('G'),
                'skybillRank' => 1,
                'weight' => 2,
                'weightUnit' => 'KGM',
                'height' => '10',
                'length' => '30',
                'width' => '40',
            ],
            //STRUCTURE SKYBILLPARAMSVALUE
            'skybillParamsValue' => [
                'mode' => 'SLT|PDF|XML|XML2D',
                'withReservation' => '1',
            ],
            //OTHERS
            'password' => '255562',
            'modeRetour' => '3',
            'numberOfParcel' => '1',
            'version' => '',
            'multiparcel' => 'N'
        ];

        // YOU CAN FIND PARAMETERS YOU NEED IN HERE
        //var_dump($client_ch->__getFunctions());
        //var_dump($client_ch->__getTypes());

        $results = $clientCh->shippingMultiParcelV2($params);
        //$reservation = $results->return->reservationNumber;
        $etiquette = $results->return->resultMultiParcelValue;

        try {
            //Objet StdClass
            $results = $clientCh->shippingMultiParcelV2($params);
        } catch (SoapFault $soapFault) {
            //var_dump($soapFault);
            echo "Request :<br>", htmlentities($clientCh->__getLastRequest()), "<br>";
            echo "Response :<br>", htmlentities($clientCh->__getLastResponse()), "<br>";
        }

        $this->addFlash("success", "Félicitations, tu vas recevoir un sms contenant le numéro à 
        présenter au bureau de poste");

        return new Response($etiquette);
    }
}
