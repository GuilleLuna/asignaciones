<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response; 
use AppBundle\Entity\Task;
use AppBundle\Form\TaskType;
use Symfony\Component\Security\Core\User\UserInterface;

class TaskController extends Controller
{
    
    public function indexAction(){
        $em = $this ->getDoctrine()->getManager();
        // $sql = "SELECT t FROM AppBundle:Task t ORDER BY t.id DESC";
        // $task= $em -> createQuery($sql);
        $task = $em -> getRepository('AppBundle:Task')-> findAll();
        
        
        
        return $this-> render('Task/index.html.twig',array('tasks'=>$task));
    }
    
    public function customAction(Request $request){
        
        $iduser= $this->get('security.token_storage')->getToken()->getUser()->getId();
    
        // exit($iduser);
        $em = $this -> getDoctrine()->getManager();
        $sql = "SELECT t from Task t JOIN t.user u WHERE u.id= 2";
        
        $task = $em->createQuery($sql);
        
        return $this -> render('Task/custom.html.twig',array('task'=> $task));
    }
    
    public function addAction(){
        $task = new Task();
        $form = $this -> createCreateForm($task);
        return $this -> render('Task/add.html.twig',array('form' => $form -> createView()));
    }
    
    public function createCreateForm(Task $entity){
        
        $form = $this->createForm(TaskType::class, $entity, array('action' => $this->generateUrl('task_create'),
        'method' => 'POST'));
        
        return $form;
    }
    
    public function createAction(Request $request){
        $task=new Task();
        $form = $this -> createCreateForm($task);
        $form -> handleRequest($request);
        
        if($form ->isValid()){
            $task->setStatus(0);
            $em = $this->getDoctrine()->getManager();
            $em ->persist($task);
            $em -> Flush();
            
            $this -> addFlash('Mensaje','La tarea ha sido creada');
            return $this-> redirectToRoute('task_index');
        }
        return $this -> render('Task/add.html.twig',array('form' => $form -> createView()));
        
    }
    
    
    public function viewAction($id){
        
        $task= $this-> getDoctrine()->getRepository('AppBundle:Task')->find($id);
        
        if(!$task){
            throw $this -> createNotFoundException('La tarea no existe');
        }
        
        
        $deleteForm = $this -> createCustomForm($task->getid(),'DELETE','task_delete');
        
        $user = $task -> getUser();
        
        return $this -> render ('Task/view.html.twig',array('task'=>$task,'user'=>$user,'delete_form'=>$deleteForm->createView()));
    }
    
    
    
    public function editAction($id){
       
        $em = $this -> getDoctrine()->getManager();
        
        $task = $em -> getRepository('AppBundle:Task')->find($id);
        
         if(!$task){
            throw $this -> createNotFoundException('La tarea no existe');
        }
        
        
        $form = $this -> createEditForm($task);
        
        return $this -> render ('Task/edit.html.twig',array('task'=>$task,'form'=>$form->createView()));
    }
    
    private function createEditForm(Task $entity){
        
        $form = $this ->createForm(TaskType::class, $entity, array('action' => $this ->generateUrl('task_update' ,array('id'=> $entity->getId())),
        'method'=>'PUT'));
        
        return $form;    
    }
    
    public function updateAction($id,Request $request){
        
        $em = $this -> getDoctrine()->getManager();
        
        $task = $em ->getRepository('AppBundle:Task')->find($id);
        
        
        if(!$task){
            throw $this -> createNotFoundException('La tarea no existe');
        }
        
        
        $form = $this -> createEditForm($task);
        $form -> handleRequest($request);
       
        if($form->isSubmitted() and $form->isValid()){
            $task->setStatus(0);
            $em->flush();
            $successMessage = ('La tarea ha sido modificada');
            $this->addFlash('Mensaje', $successMessage);            
            return $this->redirectToRoute('task_edit', array('id' => $task->getId()));
            
        }
        
        return $this ->render ('Task/edit.html.twig',array('task'=>$task,'form'=> $form->createView()));
        
    }
    
    
    public function deleteAction(Request $request,$id){
        
        $em = $this->getDoctrine()->getManager();
        
        $task = $em ->getRepository('AppBundle:Task')->find($id);
        
        if(!$task){
            throw $this -> createNotFoundException('La tarea no existe');
        }
        
        $form = $this-> createCustomForm($task->getId(),'DELETE','task_delete');
        $form-> handleRequest($request);
        
         if($form->isSubmitted() and $form->isValid()){
            $em->remove($task);
            $em->flush();
            $successMessage = ('La tarea ha sido borrada');
            $this->addFlash('Mensaje', $successMessage);            
            return $this->redirectToRoute('task_index', array('id' => $task->getId()));
            
        }
        
    }
    
    
    private function createCustomForm($id, $method,$route){
        
        return $this->createFormBuilder()
            ->setAction($this->generateUrl($route,array('id'=>$id)))
            ->setMethod($method)
            ->getForm();
    }
}
