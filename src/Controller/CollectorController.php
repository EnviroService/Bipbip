<?php

namespace App\Controller;

use App\Entity\Estimations;
use App\Entity\Organisms;
use App\Entity\Reporting;
use App\Form\CollectUserType;
use App\Form\ImeiType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("collector")
 */

class CollectorController extends AbstractController
{

// Vérification du téléphone et de l'IMEI par le collecteur

    /**
     * @Route("/verify/estim/{id}", name="verifyEstim", methods={"GET","POST"})
     * @IsGranted("ROLE_COLLECTOR")
     * @param Request $request
     * @param Estimations $estimation
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function verifyEstim(Request $request, Estimations $estimation, EntityManagerInterface $em): Response
    {
        // if collector, verify that estimation belongs to collector's organism
        // unless redirect him to estimations_index
        $userRoles = $this->getUser()->getRoles();
        foreach ($userRoles as $role) {
            if ($role == "ROLE_COLLECTOR") {
                $organismUser = $this->getUser()->getOrganism();
                if ($estimation->getUser() !== null) {
                    $organismCollector = $estimation->getUser()->getOrganism();
                } else {
                    $organismCollector = new Organisms();
                }
                if ($organismUser != $organismCollector) {
                    // Unless prepare flash message and redirect
                    $message = "Cette estimation n'appartient pas à votre organisme";
                    $this->addFlash('danger', $message);
                    return $this->redirectToRoute('estimations_index');
                }
            }
        }

        $form = $this->createForm(ImeiType::class, $estimation);
        $form->handleRequest($request);
        $id = $estimation->getId();

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $imei = $data->getImei();
            $estimation->setImei($imei);

            $em->persist($estimation);
            $em->flush();

            return $this->redirectToRoute('verifyEstim', [
                'id' => $id,
            ]);
        }

        return $this->render('estimations/editEstim.html.twig', [
            'estimation' => $estimation,
            'form' => $form->createView(),
        ]);
    }
// Vérifie les infos du users
    /**
     * @Route("/verify/user/{id}", name="verifyUser", methods={"GET","POST"})
     * @IsGranted("ROLE_COLLECTOR")
     * @param Request $request
     * @param Estimations $estimation
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function verifyUser(
        Request $request,
        Estimations $estimation,
        EntityManagerInterface $em
    ): Response {
        // if collector, verify that estimation belongs to collector's organism
        // unless redirect him to estimations_index
        $userRoles = $this->getUser()->getRoles();
        foreach ($userRoles as $role) {
            if ($role == "ROLE_COLLECTOR") {
                $organismUser = $this->getUser()->getOrganism();
                if ($estimation->getUser() !== null) {
                    $organismCollector = $estimation->getUser()->getOrganism();
                } else {
                    $organismCollector = new Organisms();
                }
                if ($organismUser != $organismCollector) {
                    // Unless prepare flash message and redirect
                    $message = "Cette estimation n'appartient pas à votre organisme";
                    $this->addFlash('danger', $message);
                    return $this->redirectToRoute('estimations_index');
                }
            }
        }

        $user = $estimation->getUser();
        $form = $this->createForm(CollectUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $user) {
            $data = $form-> getData();
            $user->setLastname($data['lastname'])
                ->setFirstname($data['firstname'])
                ->setEmail($data['email'])
                ->setPhoneNumber($data['phoneNumber'])
                ->setAddress($data['address'])
                ->setPostCode($data['postCode'])
                ->setCity($data['city']);

            $em->persist($user);
            $em->flush();
        }

        return $this->render('bdc/editUser.html.twig', [
            'estimation' => $estimation,
            'form' => $form->createView(),
        ]);
    }
// Permet d'uploader la CNI du user et de la stocker dans uploads/CI, crée un bon de cession stocker dans uploads/BDC

    /**
     * @Route("/capture/{id}", name="takePhoto")
     * @IsGranted("ROLE_COLLECTOR")
     * @param Estimations $estimation
     * @param EntityManagerInterface $em
     * @return Response
     */
    // route to take a photo of the Identity Card
    public function takePhoto(Estimations $estimation, EntityManagerInterface $em)
    {
        // if collector, verify that estimation belongs to collector's organism
        // unless redirect him to estimations_index
        $userRoles = $this->getUser()->getRoles();
        foreach ($userRoles as $role) {
            if ($role == "ROLE_COLLECTOR") {
                $organismUser = $this->getUser()->getOrganism();
                if ($estimation->getUser() !== null) {
                    $organismCollector = $estimation->getUser()->getOrganism();
                } else {
                    $organismCollector = new Organisms();
                }
                if ($organismUser != $organismCollector) {
                    // Unless prepare flash message and redirect
                    $message = "Cette estimation n'appartient pas à votre organisme";
                    $this->addFlash('danger', $message);
                    return $this->redirectToRoute('estimations_index');
                }
            }
        }

        if (isset($_POST['submit'])) {
            if (!$estimation->getUser()) {
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
            if (!empty($estimation->getUser())) {
                $lastname = $estimation->getUser()->getLastname();
                $firstname = $estimation->getUser()->getFirstname();
            } else {
                $lastname = "anonyme";
                $firstname = "anonyme";
            }
            $extension = pathinfo($_FILES['upload']['name'], PATHINFO_EXTENSION);
            $filename = 'E' . $estimation->getId() . '-' . $lastname . '-' . $firstname . '.' . $extension;
            $filePath = "uploads/CI/$filename";

            if (move_uploaded_file($tmpFilePath, $filePath)) {
                $message = 'Merci, la photo a été enregistrée';
                $this->addFlash('success', $message);
            } else {
                $error = 'Merci de créer un dossier uploads/CI/';
                $this->addFlash('danger', $error);
            }
            // Validation of isValidatedCi in DB
            $estimation->setIsValidatedCi(true);
            $em->persist($estimation);
            $em->flush();
            return $this->redirectToRoute('bdc_show', [
                'estimation' => $estimation,
                'id' => $estimation->getId(),
                ]);
        }
        return $this->render('bdc/takePhoto.html.twig', [
            'estimation' => $estimation,
        ]);
    }
// Création du bdc récapitulatif entre le user et le collecteur

    /**
     * @Route("/show/{id}", name="bdc_show")
     * @IsGranted("ROLE_COLLECTOR")
     * @param Estimations $estimation
     * @return Response
     */
    // route to show an estimation
    public function show(Estimations $estimation)
    {
        // if collector, verify that estimation belongs to collector's organism
        // unless redirect him to estimations_index
        $userRoles = $this->getUser()->getRoles();
        foreach ($userRoles as $role) {
            if ($role == "ROLE_COLLECTOR") {
                $organismUser = $this->getUser()->getOrganism();
                if ($estimation->getUser() !== null) {
                    $organismCollector = $estimation->getUser()->getOrganism();
                } else {
                    $organismCollector = new Organisms();
                }
                if ($organismUser != $organismCollector) {
                    // Unless prepare flash message and redirect
                    $message = "Cette estimation n'appartient pas à votre organisme";
                    $this->addFlash('danger', $message);
                    return $this->redirectToRoute('estimations_index');
                }
            }
        }

        if ($this->getUser()->getRoles()[0] == "ROLE_ADMIN") {
            return $this->render('bdc/show.html.twig', [
                'estimation' => $estimation,
            ]);
        } elseif ($estimation->getUser() !== null && $estimation->getUser()->getOrganism() !== null) {
            if (($estimation->getUser()->getOrganism() === $this->getUser()->getOrganism())) {
                return $this->render('bdc/show.html.twig', [
                    'estimation' => $estimation,
                ]);
            }
        }
        $message = "Cette estimation n'est pas liée à un utilisateur";
        $this->addFlash('danger', $message);
        return $this->redirectToRoute('adminIndex');
    }

    // route to go to payment

    /**
     * @Route("/pay/{id}", name="bdc_pay")
     * @IsGranted("ROLE_COLLECTOR")
     * @param Estimations $estimation
     * @return Response
     */

    public function pay(Estimations $estimation)
    {
        // if collector, verify that estimation belongs to collector's organism
        // unless redirect him to estimations_index
        $userRoles = $this->getUser()->getRoles();
        foreach ($userRoles as $role) {
            if ($role == "ROLE_COLLECTOR") {
                $organismUser = $this->getUser()->getOrganism();
                if ($estimation->getUser() !== null) {
                    $organismCollector = $estimation->getUser()->getOrganism();
                } else {
                    $organismCollector = new Organisms();
                }
                if ($organismUser != $organismCollector) {
                    // Unless prepare flash message and redirect
                    $message = "Cette estimation n'appartient pas à votre organisme";
                    $this->addFlash('danger', $message);
                    return $this->redirectToRoute('estimations_index');
                }
            }
        }

        return $this->render('bdc/pay.html.twig', [
            'estimation' => $estimation,
        ]);
    }
    // Route to confirm picture of identity Card

    /**
     * @Route("/end/{id}", name="bdc_end")
     * @IsGranted("ROLE_COLLECTOR")
     * @param Estimations $estimation
     * @param EntityManagerInterface $em
     * @return Response
     * @throws Exception
     */
    public function end(Estimations $estimation, EntityManagerInterface $em)
    {
        // if collector, verify that estimation belongs to collector's organism
        // unless redirect him to estimations_index
        $userRoles = $this->getUser()->getRoles();
        foreach ($userRoles as $role) {
            if ($role == "ROLE_COLLECTOR") {
                $organismUser = $this->getUser()->getOrganism();
                if ($estimation->getUser() !== null) {
                    $organismCollector = $estimation->getUser()->getOrganism();
                } else {
                    $organismCollector = new Organisms();
                }
                if ($organismUser != $organismCollector) {
                    // Unless prepare flash message and redirect
                    $message = "Cette estimation n'appartient pas à votre organisme";
                    $this->addFlash('danger', $message);
                    return $this->redirectToRoute('estimations_index');
                }
            }
        }

        // Validation of estimation and payment
        $estimation
            ->setIsValidatedPayment(true)
            ->setIsCollected(true)
            ->setStatus(1);

        // Adding of a line in reporting table
        $reporting = new Reporting();
        $reporting
            ->setReportype('collected')
            ->setDatereport(new DateTime('now'))
            ->setEstimation($estimation);

        $em->persist($estimation);
        $em->persist($reporting);
        $em->flush();
        return $this->render('bdc/end.html.twig', [
            'estimation' => $estimation,
        ]);
    }
}
