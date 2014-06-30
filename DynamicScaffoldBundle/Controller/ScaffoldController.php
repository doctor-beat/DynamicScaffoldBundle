<?php
/*
 * Limitations:
 * - only works with entity with a single key (e.g. 1 column key)
 * - only works with Doctrine
 * 
 * Configuration:
 * - entities must each be flagged as scaffoldable with a constant 'SCAFFOLD' with value true
 * - uses the default entity-manager OR from a parameter 'scaffold_entity_manager'
 * 
 * TODO:
 * - enable csrf
 * - css styling
 * - handle more field-types properly
 * - handle relations between entities (complex)
 * - field validation etc.
 * - paginate the index pages
 * - handle multiple entiymanagers
 * - make it work against propel
 * - (maybe) cache the metadata for better performance
 * - split the controller into proper models/gof-classes
 */

namespace DynamicScaffoldBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Doctrine\Bundle\DoctrineBundle\Mapping\MetadataFactory;


/**
 * Controller for the Gofos actions
 * @Route("")
 */
class ScaffoldController extends Controller {
    const ENTITY_MGR_PARAM = 'scaffold_entity_manager';
    
    
    /**
     * list
     * @return Response
     * @Route("", name="scaffold")
     * @Method("GET")
     * @Template()
     */
    public function indexAction() {
        $metadata = null;
        $entities = array();
        $bundles = array();
        
        #show the listing all all bundles
        return array('bundles' => $this->getBundles());
    }
    
    /**
     * list
     * @return Response
     * @Route("/{bundlename}", name="scaffold_entities")
     * @Method("GET")
     * @Template()
     */
    public function indexEntitiesAction($bundlename) {
        $bundle = $this->get('kernel')->getBundle($bundlename);
        $namespace = $bundle->getNamespace();
        
        $entities = array();
        
        $regex = '/\.php$/i';
        $files = preg_replace($regex, '', preg_grep($regex, scandir($bundle->getPath() . '/Entity')));
        foreach ($files as $file) {
            $classname =  $namespace . '\\Entity\\' . $file; 
            #if ($this->hasScaffoldEnabled($classname)) {
                $entities[$file] = $this->hasScaffoldEnabled($classname);
            #}
        }
        
        return array(
            'bundle'=> $bundlename, 
            'entities' => $entities,
            );
        
    }
    /**
     * list
     * @return Response
     * @Route("/{bundlename}/{entityname}", name="scaffold_list")
     * @Method("GET")
     * @Template()
     */
    public function listAction($bundlename, $entityname) {

        $repository = $bundlename . ':' . $entityname;
        $class = $this->getClass($repository, false);
        $metadata = $this->getMetadata($class);

        $em = $this->getEM();
        $entities = $em->getRepository($repository)->findAll();
        
         
        return array(
            'bundle'=> $bundlename, 
            'entityname' => $entityname,
            'metadata' => $metadata,
            'fields' => $metadata ? $metadata->fieldMappings : null,
            'entities' => $entities,
        );
    }
    /**
     * new form
     *
     * @Route("/{bundlename}/{entityname}/new", name="scaffold_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction($bundlename, $entityname)
    {
        $repository = $bundlename . ':' . $entityname;
        $class = $this->getClass($repository);
        $metadata = $this->getMetadata($class);
        
        $entity = new $class();
        $form   = $this->createCreateForm($entity, $metadata, $bundlename, $entityname);

        return array(
            'bundle'=> $bundlename, 
            'entityname' => $entityname,
            'metadata' => $metadata,
            'id'          => null,
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }
    /**
     * create
     *
     * @Route("/{bundlename}/{entityname}", name="scaffold_create")
     * @Method("POST")
     * @Template("DynamicScaffoldBundle:Scaffold:new.html.twig")
     */
    public function createAction(Request $request, $bundlename, $entityname) {
        return $this->editAction($request, $bundlename, $entityname);
    }
    
    /**
     * edit
     *
     * @Route("/{bundlename}/{entityname}/edit/{id}", name="scaffold_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction(Request $request, $bundlename, $entityname, $id = null)
    {
        $repository = $bundlename . ':' . $entityname;
        $class = $this->getClass($repository);
        $metadata = $this->getMetadata($class);
        
        $em = $this->getEM();
        
        if (! is_null($id)) {
            $entity = $em->getRepository($repository)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find entity.');
            }
        
            $form = $this->createEditForm($entity, $metadata, $id, $bundlename, $entityname);
            #$deleteForm = $this->createDeleteForm($id);
        } else {
            $entity = new $class();
            $em->persist($entity);

            $form = $this->createCreateForm($entity, $metadata, $bundlename, $entityname);
            #$deleteForm = null;
        }

        
        if ($request->getMethod() == 'PUT' || $request->getMethod() == 'POST') {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $em->flush();

                return $this->redirect($this->generateUrl('scaffold_list', array(
                    'bundlename'=> $bundlename, 
                    'entityname' => $entityname,
                )));
            }            
        }

        return array(
            'bundle'=> $bundlename, 
            'entityname' => $entityname,
            'metadata' => $metadata,
            'id'          => $id,
            'entity'      => $entity,
            'form'   => $form->createView(),
           # 'delete_form' => is_null($deleteForm) ? null : $deleteForm->createView(),
        );
    }
    /**
     * Edits an existing StdProductContainer entity.
     *
     * @Route("/{bundlename}/{entityname}/{id}", name="scaffold_update")
     * @Method("PUT")
     * @Template("DynamicScaffoldBundle:Scaffold:edit.html.twig")
     */
    public function updateAction(Request $request, $bundlename, $entityname, $id)
    {
        return $this->editAction($request, $bundlename, $entityname, $id);
    }

    /**
     * delete
     *
     * @Route("/{bundlename}/{entityname}/delete/{id}", name="scaffold_delete_form")
     * @Method("GET")
     * @Template()
     */
    public function deleteFormAction($id, $bundlename, $entityname)
    {
        $repository = $bundlename . ':' . $entityname;
        $class = $this->getClass($repository);
        $metadata = $this->getMetadata($class);
        
        $em = $this->getEM();
        
        $entity = $em->getRepository($repository)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find entity.');
        }

        $form = $this->createDeleteForm($id,  $bundlename, $entityname);

        return array(
            'bundle'=> $bundlename, 
            'entityname' => $entityname,
            'metadata' => $metadata,
            'id'          => $id,
            'fields' => $metadata->fieldMappings,
            'entity'      => $entity,
            'form'        => $form->createView(),
        );
    }

    /**
     * Deletes
     *
     * @Route("/{bundlename}/{entityname}/{id}", name="scaffold_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $bundlename, $entityname, $id)
    {
        $repository = $bundlename . ':' . $entityname;
        $class = $this->getClass($repository);
        #$metadata = $this->getMetadata($class);

        $form = $this->createDeleteForm($id,$bundlename, $entityname);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getEM();
            $entity = $em->getRepository($repository)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('scaffold_list', array(
            'bundlename'=> $bundlename, 
            'entityname' => $entityname,
        )));
    }

/////////////////////////////////////////////////////////////////////////////
    
    private function createCreateForm($entity, $metadata, $bundlename, $entityname)
    {
        #$form = $this->createForm(new StdProductContainerType(), $entity, array(
        #    'action' => $this->generateUrl('stdproductcontainer_create'),
        #    'method' => 'POST',
        #));
        $id = null;
        $builder = $this->getFormBuilder($entity, $metadata, $id)
            ->setAction($this->generateUrl('scaffold_create', array(
            'bundlename'=> $bundlename, 
            'entityname' => $entityname,
        )))
            ->setMethod('POST')
            ->add('submit', 'submit', array('label' => 'Create'));

        return $builder->getForm();
    }
    private function createEditForm($entity, $metadata, $id, $bundlename, $entityname) {
        $builder = $this->getFormBuilder($entity, $metadata, $id)
            ->setAction($this->generateUrl('scaffold_update', array('id' => $id, 
            'bundlename'=> $bundlename, 
            'entityname' => $entityname,
        )))
            ->setMethod('PUT')
            ->add('submit', 'submit', array('label' => 'Update'));
        
        return $builder->getForm();
    }
    
    private function createDeleteForm($id, $bundlename, $entityname) {
        return $this->getFormBuilder(null, null, $id)
            ->setAction($this->generateUrl('scaffold_delete', array('id' => $id, 
            'bundlename'=> $bundlename, 
            'entityname' => $entityname,
        )))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete row'))
            ->getForm()
        ;
    }
    
    private function getFormBuilder($entity, $metadata = null, $id) {
        $builder = $this->createFormBuilder($entity, array(  'csrf_protection' => false));

#        echo '<pre>';
#        var_dump($metadata);
#        echo '</pre>';
        
        if ($metadata) {
            foreach ($metadata->fieldMappings as $field => $details) {
                #skip id columns
                if (! @$details['id']) {
                    $type = null;
                    $opts = array('required' => ! $details['nullable'] );
                    switch ($details['type']) {
                        case 'smallint':
                        case 'bigint':
                        case 'integer':
                            $type = 'integer';
                            break;
                        case 'decimal':
                            $type = 'number';
                            break;
                        case 'text':
                            $type = 'textarea';
                            $opts["attr"] = array("cols" => "50", "rows" => 5);
                            break;
                        case 'boolean':
                            $type = 'checkbox';
                            $opts['required'] = false;      //check box is never required
                            break;
                        case 'date':
                            $type = 'datetime';
                            $opts['widget'] = 'single_text';
                            break;
                        case 'datetime':
                        case 'datetimetz':
                            $type = 'datetime';
                            break;
                        case 'time':
                            $type = 'time';
                            break;
                        case 'string':
                        default: 
                            $type = 'text';
                            break;
                    }
                    $builder->add($field, $type, $opts);
                }
            }
        }
        
        return $builder;
    }
    
    private function getMetadata($class) {
        $factory = new MetadataFactory($this->get('doctrine'));
        return $factory->getClassMetadata($class)->getMetadata()[0];
    }

 
    private function getClass($repositoryName, $quitOnError = true) {
        if (preg_match('/:/', $repositoryName)) {
            $em = $this->getEM();
            $class = $em->getRepository($repositoryName)->getClassName();

            if (! $this->hasScaffoldEnabled($class)) {
                throw new \Exception('scaffolding is disabled for this entity');
            }
            return $class;

        } elseif ($quitOnError) {
            throw new \Exception('not a proper repository');
        }
    }
    
    private function hasScaffoldEnabled($class) {
        return defined("$class::SCAFFOLD") &&  $class::SCAFFOLD === true;
    }

    

    private function getEM() {
        $name = null;
        if ($this->container->hasParameter(self::ENTITY_MGR_PARAM)) {
            $name = $this->container->getParameter(self::ENTITY_MGR_PARAM);
        }
        
        return $this->getDoctrine()->getManager($name);
    }

    /**
     * gets all bundles (as string) that have a Entity directory
     * @return type
     */
    private function getBundles() {
        $bundles = array();
        #get all bundles with an entity folder:
        $kernel = $this->get('kernel');
        foreach ($kernel->getBundles() as $bundle) {
            $dir = $bundle->getPath() . '/Entity';
            if (is_dir($dir)) {
                $bundles[]= $bundle->getName();
            }
        }
        return $bundles;
    }
}
