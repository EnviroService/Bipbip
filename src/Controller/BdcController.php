<?php

namespace App\Controller;

use App\Entity\Estimations;
use App\Entity\User;
use App\Form\EstimationsType;
use App\Repository\EstimationsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * @Route("admin/bdc")
 */

class BdcController extends AbstractController
{
    /**
     * @Route("/", name="bdc_index")
     */
    public function index()
    {
        // list all pdf in public/uploads/BDC/
        $files = scandir('uploads/BDC/');

        return $this->render('bdc/index.html.twig', [
            'files' => $files
        ]);
    }

    /**
     * @Route("/verify/{id}", name="verifyEstim", methods={"GET","POST"})
     * @param Request $request
     * @param Estimations $estimation
     * @return Response
     */
    public function verifyEstim(Request $request, Estimations $estimation): Response
    {
        $form = $this->createForm(EstimationsType::class, $estimation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('takePhoto');
        }

        return $this->render('estimations/edit.html.twig', [
            'estimation' => $estimation,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/capture/{id}", name="takePhoto")
     * @param Estimations $estimation
     * @param UserRepository $user
     * @return Response
     */
    // route to take a photo of the Identity Card
    public function takePhoto(Estimations $estimation, UserRepository $user)
    {
        if (isset($_POST['submit'])) {
            if ($estimation->getUser()) {
                $user = $this->getUser();
            } else {
                $message = "Cette estimation n'est pas liée à un utilisateur";
                $this->addFlash('danger', $message);
                return $this->redirectToRoute('home');
            }
            // is the file exist ?
            $tmpFilePath = $_FILES['upload']['tmp_name'];
            if (!is_uploaded_file($tmpFilePath)) {
                $error = 'Le fichier est introuvable';
                $this->addFlash('danger', $error);
                return $this->render('bdc/takePhoto.html.twig', [
                    'estimation' => $estimation,
                    ]);
            }
            //save the url and the file
            $extension = pathinfo($_FILES['upload']['name'], PATHINFO_EXTENSION);
            $filename = 'E' . $estimation->getId() . '-' . $user->getLastname() . '-' . $user->getFirstname()
                        . '.' . $extension;
            $filePath = "uploads/CI/$filename";

            if (move_uploaded_file($tmpFilePath, $filePath)) {
                $message = 'Merci, la photo a été enregistrée';
                $this->addFlash('success', $message);
            } else {
                $error = 'Merci de créer un dossier uploads/CI/';
                $this->addFlash('danger', $error);
            }
                return $this->redirectToRoute('bdc_show', [
                'id' => $estimation->getId(),
                ]);
        }
        return $this->render('bdc/takePhoto.html.twig', [
            'estimation' => $estimation,
        ]);
    }

    /**
     * @Route("/show/{id}", name="bdc_show")
     * @param Estimations $estimation
     * @return Response
     */
    // route to show an estimation
    public function show(Estimations $estimation)
    {
        return $this->render('bdc/show.html.twig', [
            'IMEI' => "355 402 092 374 478",
            'estimation' => $estimation,
        ]);
    }

    /**
     * @Route("/pdf/{id}", name="bdc_pdf")
     * @param Estimations $estimation
     * @return RedirectResponse
     */
    // route to generate a PDF from estimation
    public function showPDF(Estimations $estimation)
    {
        // Configure Dompdf according to your needs
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');

        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);

        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('bdc/bdc.html.twig', [
            'IMEI' => "355 402 092 374 478",
            'estimation' => $estimation
        ]);

        // Create Filename
        $clientId = $this->getUser();
        $estimationId = $estimation->getId();
        $filename = date("Ymd") . "C" . $clientId . "P" . $estimationId . ".pdf";

        // Load HTML to Dompdf
        $dompdf->loadHtml($html);

        // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        // Store PDF Binary Data
        $output = $dompdf->output();

        // we want to write the file in the public directory
        $publicDirectory = 'uploads/BDC';
        $pdfFilepath =  $publicDirectory . '/' . $filename;

        // Write file to the desired path
        file_put_contents($pdfFilepath, $output);

        // Prepare flash message
        $message = "Le bon de cession a été généré";
        $this->addFlash('success', $message);

        // Output the generated PDF to Browser (inline view)
        $dompdf->stream($filename, [
        "Attachment" => false
        ]);

        return $this->redirectToRoute('bdc_pay');
    }

    /**
     * @Route("/pay/{id}", name="bdc_pay")
     * @param Estimations $estimation
     * @return Response
     */
    // route to go to payment
    public function pay(Estimations $estimation)
    {
        return $this->render('bdc/pay.html.twig', [
            'estimation' => $estimation,
        ]);
    }
}
