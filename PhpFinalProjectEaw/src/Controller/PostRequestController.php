<?php

namespace App\Controller;

use App\Entity\PostRequest;
use App\Form\PostRequestType;
use App\Repository\PostRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/post/request")
 */
class PostRequestController extends AbstractController
{
    /**
     * @Route("/", name="post_request_index", methods={"GET"})
     */
    public function index(PostRequestRepository $postRequestRepository): Response
    {
        return $this->render('post_request/index.html.twig', [
            'post_requests' => $postRequestRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="post_request_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $postRequest = new PostRequest();
        $form = $this->createForm(PostRequestType::class, $postRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($postRequest);
            $entityManager->flush();

            return $this->redirectToRoute('post_request_index');
        }

        return $this->render('post_request/new.html.twig', [
            'post_request' => $postRequest,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="post_request_show", methods={"GET"})
     */
    public function show(PostRequest $postRequest): Response
    {
        return $this->render('post_request/show.html.twig', [
            'post_request' => $postRequest,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="post_request_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, PostRequest $postRequest): Response
    {
        $form = $this->createForm(PostRequestType::class, $postRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('post_request_index');
        }

        return $this->render('post_request/edit.html.twig', [
            'post_request' => $postRequest,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="post_request_delete", methods={"DELETE"})
     */
    public function delete(Request $request, PostRequest $postRequest): Response
    {
        if ($this->isCsrfTokenValid('delete'.$postRequest->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($postRequest);
            $entityManager->flush();
        }

        return $this->redirectToRoute('post_request_index');
    }
}
