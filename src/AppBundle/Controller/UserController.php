<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\User;
use AppBundle\Form\UserType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;

class UserController extends Controller
{
   
   
   public function homeAction(){
       
       return $this -> render('User/home.html.twig');
   }
   
    public function indexAction(Request $request)
    {
        $em = $this -> getDoctrine()-> getManager();
        $users = $em -> getRepository('AppBundle:User')-> findAll();
        
        // $res = 'Lista de usuarios: <br/>';
        
        // foreach($users as $user){
        //     $res .= 'Usuaio: '. $user->getUsername() . ' - ' . $user-> getEmail() .'<br/>';
        // }
        
        // return new Response($res);
        
        
        $deleteFormAjax = $this -> createCustomForm(':USER_ID','DELETE','user_delete');
     
        
        return $this->render('User/index.html.twig',array('users' => $users,'delete_form_ajax'=> $deleteFormAjax-> createView()));
    }
    
    public function addAction()
    {
        $user = new User();
        $form = $this -> createCreateForm($user);
        return $this -> render('User/add.html.twig',array('form' => $form -> createView()));
    }
    
    private function createCreateForm(User $entity){
        
        $form = $this->createForm(UserType::class, $entity, array('action' => $this->generateUrl('user_create'),
        'method' => 'POST'));
        
        return $form;
    }
    
    public function createAction(Request $request){
        
        $user = new User();
        $form = $this -> createCreateForm($user);
        $form  -> handleRequest($request);
        
        if($form -> isValid()){
            
            $password = $form -> get('password') -> getData();
            
            $passwordConstraint = new Assert\NotBlank();
            $errorlist = $this -> get('validator')-> validate($password,$passwordConstraint);
            
            if(count($errorlist) == 0){
                    
                $encoder = $this -> container -> get('security.password_encoder');
                $encoded = $encoder -> encodePassword($user, $password);
                    
                $user -> setPassword($encoded);
                    
                $em = $this -> getDoctrine() -> getManager();
                $em -> persist ($user);
                $em -> flush();
                    
                $this -> addFlash('Mensaje','El usuario ha sido creado');
                    
                return $this -> redirectToRoute('user_index');
            }else{
                $errorMessages = new FormError($errorlist[0]->getMessage());
                $form -> get('password') ->addError($errorMessages);
            }
            
           
        }
        
        return $this -> render('User/add.html.twig',array('form' => $form -> createView()));
    }


    public function viewAction($id)
    {
       
        $repository = $this ->getDoctrine()-> getRepository('AppBundle:User') ;
   
        $user = $repository ->find($id);
        if(!$user){
           
           $messeageException = $this -> get('Usuario no encontrado');
           throw $this->creteNotFoundException($messeageException);
           
       }
       
       $deleteForm = $this ->createCustomForm($user -> getId(),'DELETE','user_delete');
       
        
        return $this -> render('User/viewAction.html.twig',array('user' => $user, 'delete_form'=>$deleteForm->createView()));
    }
    
  
    
    public function deleteAction(Request $request, $id)
    {
       $em = $this -> getDoctrine()->getManager();
       $user = $em -> getRepository('AppBundle:User')-> find($id);
       
       
        if(!$user){
           
           $messeageException = 'Usuario no encontrado';
           throw $this->creteNotFoundException($messeageException);
           
       }
       
        $allUser =  $em -> getRepository('AppBundle:User')->findAll();
        $countUser =count($allUser);
       
    //  $form = $this ->createDeleteForm($user);
        $form = $this -> createCustomForm($user->getID(),'DELETE','user_delete');
        $form  -> handleRequest($request);
       
       if($form -> isSubmitted() && $form -> isValid()){
           
          if($request -> isXMLHttpRequest()){
              $res = $this -> deleteUser($user -> getRole(),$em, $user);
              
              return new Response( json_encode(array('remove' => $res ['remove'],'message' => $res['message'],'countUser' => $countUser)),200,array('Content-Type'=>'aplication/json'));
          
          }
           
          $res = $this -> deleteUser($user -> getRole(), $em, $user);
          
           $this -> addFlash($res['alert'],$res['message']);
           return $this -> redirectToRoute('user_index');
       }
       
    }
    
    private function deleteUser($role,$em,$user){
        if($role == 'ROLE_USER'){
            
            $em ->remove($user);
            $em -> Flush();
            
            $messeage ='El usuario ha sido eliminado';
            $remove = 1;
            $alert = 'Mensaje';
            
        }elseif($role == 'ROLE_ADMIN'){
            
            $messeage = 'El usuario ADMIN no puede ser eliminado';
            $remove = 0;
            $alert = 'error';
            
        }
        
        return array('remove' => $remove, 'message' => $messeage, 'alert' => $alert);
    }
    
    private function createCustomForm($id, $method,  $route){
        return $this -> createFormBuilder()
        ->setAction($this -> generateUrl($route, array('id' => $id)))
        ->setMethod($method)
        -> getForm();
    }
    
    public function editAction($id)
    {
       $em = $this -> getDoctrine()->getManager();
       $user = $em -> getRepository('AppBundle:User')->find($id);
       
       if(!$user){
           
           $messeageException = $this -> get('Usuario no encontrado');
           throw $this->creteNotFoundException($messeageException);
           
       }
       
       $form = $this ->createEditForm($user);
       
       return $this->render('User/edit.html.twig',array('user'=>$user, 'form'=> $form->createView()));
    }
    
    private function createEditForm(User $entity){
        $form = $this -> createForm(UserType::class, $entity, array('action'=>$this -> generateUrl('user_update',array('id' =>$entity->getId())),'method'=>'PUT'));
        
        return $form;
    }
    
    public function updateAction(User $id,Request $request){
        
        $em = $this ->getDoctrine()-> getManager();
        $user = $em -> getRepository('AppBundle:User')-> find($id);
        
        if(!$user){
           
           $messeageException = $this -> get('Usuario no encontrado');
           throw $this->creteNotFoundException($messeageException);
           
       }
       
       $form = $this -> createEditForm($user);
       $form -> handleRequest($request);
       
       if($form -> isSubmitted() && $form -> isValid()){
           
           $pass= $form ->get('password')->getData();
           if(!empty($pass)){
               
               $encoder = $this ->container->get('security.password_encoder');
               $encoded = $encoder ->encodePassword($user, $pass);
               $user -> setPassword($encoded);
           }else{
               
                $recovered = $this -> recoveredPass($id);
                $user -> setPassword($recovered[0]['password']);

               
           }
           
           $em -> Flush();
           $succesMessages = ('Usuaio Modificado correctamente');
           $this -> addFlash('Mensaje',$succesMessages);
           return $this -> redirectToRoute('user_edit', array('id'=> $user ->getId()));
       }
       
       return $this -> render('User/edit.html.twig', array('user' => $user, 'form' => $form -> createView()));
        
    }
    
    private function recoveredPass($id){
       
       
            $em = $this->getDoctrine()->getManager();
            $query = $em ->createQuery('Select u.password from AppBundle:user u Where u.id = :id')->setParameter('id',$id);
            
            $currentpass=$query->getResult();
            
       
            return $currentpass;
    }
}