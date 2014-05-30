<?php

namespace DynamicScaffoldBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Docdata\WMSBundle\Entity\StdProductContainer;
use Docdata\WMSBundle\Form\StdProductContainerType;

use Docdata\WMSBundle\Constants;


/**
 * StdProductContainer controller.
 *
 * @Route("/stdproductcontainer")
 */
class StdProductContainerController extends Controller
{
    /**
     * Lists all StdProductContainer entities.
     *
     * @Route("/", name="stdproductcontainer")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $log = $this->get(Constants::LOG_CHANNEL);
        
        $em = $this->getDoctrine()->getManager(Constants::ENTITY_MGR);
        #$em = $this->getDoctrine();

        $entities = $em->getRepository('DocdataWMSBundle:StdProductContainer')->findAll();
        
        $log->debug("Entitites: " . count($entities));

        return array(
            'entities' => $entities,
        );
    }
    /**
     * Creates a new StdProductContainer entity.
     *
     * @Route("/", name="stdproductcontainer_create")
     * @Method("POST")
     * @Template("DocdataWMSBundle:StdProductContainer:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new StdProductContainer();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $now = new \DateTime();
            $entity->setCreated($now);
            $entity->setUpdated($now);
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('stdproductcontainer_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
    * Creates a form to create a StdProductContainer entity.
    *
    * @param StdProductContainer $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createCreateForm(StdProductContainer $entity)
    {
        $form = $this->createForm(new StdProductContainerType(), $entity, array(
            'action' => $this->generateUrl('stdproductcontainer_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new StdProductContainer entity.
     *
     * @Route("/new", name="stdproductcontainer_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new StdProductContainer();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a StdProductContainer entity.
     *
     * @Route("/{id}", name="stdproductcontainer_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('DocdataWMSBundle:StdProductContainer')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find StdProductContainer entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing StdProductContainer entity.
     *
     * @Route("/{id}/edit", name="stdproductcontainer_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('DocdataWMSBundle:StdProductContainer')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find StdProductContainer entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
    * Creates a form to edit a StdProductContainer entity.
    *
    * @param StdProductContainer $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(StdProductContainer $entity)
    {
        $form = $this->createForm(new StdProductContainerType(), $entity, array(
            'action' => $this->generateUrl('stdproductcontainer_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing StdProductContainer entity.
     *
     * @Route("/{id}", name="stdproductcontainer_update")
     * @Method("PUT")
     * @Template("DocdataWMSBundle:StdProductContainer:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('DocdataWMSBundle:StdProductContainer')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find StdProductContainer entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $now = new \DateTime();
            $entity->setUpdated($now);
            $em->flush();

            return $this->redirect($this->generateUrl('stdproductcontainer_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a StdProductContainer entity.
     *
     * @Route("/{id}", name="stdproductcontainer_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('DocdataWMSBundle:StdProductContainer')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find StdProductContainer entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('stdproductcontainer'));
    }

    /**
     * Creates a form to delete a StdProductContainer entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('stdproductcontainer_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }
}
