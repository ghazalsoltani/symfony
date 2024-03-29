<?php

namespace App\Controller;

use App\Entity\Wish;
use App\Form\WishType;
use App\Repository\WishRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;


#[Route('/wish', name: 'app_wish')]
class WishController extends AbstractController
{
    #[Route('/list', name: '_list')]
    public function list(WishRepository $wishRepository): Response
    {
        $wishs = $wishRepository->findAll();

        return $this->render('wish/index.html.twig', [
            'wishs' => $wishs,
        ]);


    }
    #[Route('/detail/{id}', name: '_detail')]
    public function detail(Wish $wish): Response
    {

        return $this->render('wish/detail.html.twig', [
            'wish' => $wish
        ]);
    }



    #[Route('/create', name: '_create')]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $wish = new Wish();
        $form = $this->createForm(WishType::class, $wish);
        $form->handleRequest($request);



        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('poster_file')->getData() instanceof UploadedFile){
                $posterFile = $form->get('poster_file')->getData();
                $fileName = $slugger->slug($form->getName()).'_'.uniqid().'.'.$posterFile->guessExtension();
                $posterFile->move('posters/wish', $fileName);

                if ($wish->getPoster() && file_exists('posters/wish/'.$wish->getPoster())) {
                    unlink('posters/wish/'.$wish->getPoster());
                }
                $wish->setPoster($fileName);
            }


            $em->persist($wish);
            $em->flush();

            $this->addFlash('success', '. A +');

            return $this->redirectToRoute('app_wish_detail', ['id' => $wish->getId()]);
        }

        return $this->render('wish/edit.html.twig', [
            'wishForm' => $form,
        ]);
    }

    #[Route('/update/{id}', name: '_update', requirements: ['id'=> '\d+'])]
    public function update(Wish $wish, EntityManagerInterface $em, Request $request, $slugger): Response
    {
        $form=$this->createForm(WishType::class, $wish);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('poster_file')->getData() instanceof UploadedFile){
                $posterFile = $form->get('poster_file')->getData();
                $fileName = $slugger->slug($form->getName()).'_'.uniqid().'.'.$posterFile->guessExtension();
                $posterFile->move('posters/wish', $fileName);

                if ($wish->getPoster() && file_exists('posters/wish/'.$wish->getPoster())) {
                    unlink('posters/wish/'.$wish->getPoster());
                }
                $wish->setPoster($fileName);
            }
            $em->persist($wish);
            $em->flush();
            $this->addFlash('success', 'message');
            return $this->redirectToRoute('app_wish_create', ['id'=>$wish->getId()]);
        }
        return $this->render('wish/edit.html.twig',['wishForm'=>$wish]);
    }

    #[Route('/delete/{id}', name: '_delete', requirements: ['id'=> '\d+'])]
    public function delete(wish $wish, EntityManagerInterface $em): Response
    {
        $em->remove($wish);
        $em->flush();
        $this->addFlash('success','il ete supprimé');
        return $this->redirectToRoute('app_wish_list');
    }
}





