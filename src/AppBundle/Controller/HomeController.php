<?php

namespace AppBundle\Controller;

use AppBundle\Entity\OrderOfRing;
use AppBundle\Form\ItemFilterType;
use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

class HomeController extends Controller
{
    /**
     * @Route("/", name="homepage")
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $orderOfRing = new OrderOfRing();
        $orderOfRing->setDate(new \DateTime());
        /** @var Form $form */
        $form = $this->createFormBuilder($orderOfRing)
            ->add('name', TextType::class, ['label' => 'Vardas'])
            ->add('lastName', TextType::class, ['label' => 'Pavardė'])
            ->add('ringSize', ChoiceType::class,
                [
                    'label' => 'Dydis',
                    'choices' =>
                        [
                            '5 (15,7 mm)' => 5,
                            '6 (16,5 mm)' => 6,
                            '7 (17,3 mm)' => 7,
                            '8 (18,2 mm)' => 8,
                            '9 (18,9 mm)' => 9,
                            '10 (19,8 mm)' => 10,
                            '11 (20,6 mm)' => 11,
                            '12 (21,3 mm)' => 12,
                            '13 (22,2 mm)' => 13
                        ]
                ])
            ->add('email', TextType::class, ['label' => 'El. Paštas'])
            ->add('telephone', TextType::class, ['label' => 'Telefonas (+ šalies kodas ir telefonas pvz: +370 ...)'])
            ->add('city', TextType::class, ['label' => 'Miestas'])
            ->add('recaptcha', EWZRecaptchaType::class,
                [
                    'mapped' => false,
                    'label' => false,
                    'constraints' => array(
                        new IsTrue([
                            'message' => 'Jūs turite pažymėti, kad neesate robotas.'
                        ])
                    )
                ])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $orderOfRing = $form->getData();
            $em = $this->getDoctrine()->getManager();

            $em->persist($orderOfRing);
            $em->flush();

            return $this->redirectToRoute('thanks');
        }

        return $this->render('home/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/show_orders", name="orders_list")
     */
    public function showAction(Request $request)
    {
        $queryBuilder = $this->get('doctrine.orm.entity_manager')
            ->getRepository('AppBundle:OrderOfRing')
            ->createQueryBuilder('o');

        $form = $this->createForm(ItemFilterType::class);
        if ($request->query->has($form->getName())) {
            // manually bind values from the request
            $form->submit($request->query->get($form->getName()));

            // build the query from the given form object
            $this->get('lexik_form_filter.query_builder_updater')->addFilterConditions($form, $queryBuilder);
        }

        /**
         * @var $paginator \Knp\Component\Pager\Paginator
         */
        $paginator = $this->get('knp_paginator');
        $result = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', 15)
        );

        return $this->render(
            'orderList/orderList.html.twig',
            [
                'ordersLists' => $result,
                'form' => $form->createView()
            ]
        );
    }

    /**
     * @Route("/contacts", name="contacts")
     */
    public function contactsAction()
    {
        return $this->render('home/contacts.html.twig');
    }

    /**
     * @Route("/thanks", name="thanks")
     */
    public function thanksAction()
    {
        return $this->render('home/thankYou.html.twig');
    }
}
