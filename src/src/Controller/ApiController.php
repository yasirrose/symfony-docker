<?php
// src/Controller/ApiController.php
namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Images;
use App\Entity\UserImages;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\ImagesRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


class ApiController extends AbstractController
{   
    public function searchImages(ManagerRegistry $doctrine, Request $request): Response
    {  
        $tag = $request->query->get('tag');
        $provider = $request->query->get('provider');
        $em = $doctrine->getManager();
        $query = "SELECT i.image_path, i.tags, i.provider FROM App\Entity\Images i WHERE i.tags LIKE :tag";
        if ($provider != '') {
            $query = $query . " or i.provider LIKE :provider";
        }
        $query = $em->createQuery($query);
        $query->setParameter('tag',  '%' . $tag . '%');
        if ($provider != '') {
            $query->setParameter('provider',  '%' . $provider . '%');
        }
        $images = $query->getResult();
        return $this->json($images);
    }

    public function addImage(ManagerRegistry $doctrine, Request $request): Response
    {
        $payLoad = json_decode($request->getContent());
        $image_id = $payLoad->image_id;
        $authorizationHeader = $request->headers->get('Authorization');
        $user_id = $this->getUserDetails($doctrine, $authorizationHeader);
        
        $userImage = new UserImages();
        $userImage->setUserId($user_id);
        $userImage->setImageId($image_id);
        $entityManager = $doctrine->getManager();
        $entityManager->persist($userImage);
        $entityManager->flush();
        return $this->json(['message' => 'added image to library']);
    }

    public function getImages(ManagerRegistry $doctrine, Request $request): Response
    {
        $authorizationHeader = $request->headers->get('Authorization');
        $em = $doctrine->getManager();
        $user_id = $this->getUserDetails($doctrine, $authorizationHeader);

        $images = $em->createQuery('SELECT u.image_path, ui.user_id FROM App\Entity\Images u
              JOIN App:UserImages ui 
              WHERE
                ui.user_id=:user GROUP BY u.id')
            ->setParameter('user', $user_id)
            ->getResult();
        return $this->json(['images' => $images]);
    }

    public function getUserDetails($doctrine, $token)
    {
        $token = str_replace('Bearer ', '', $token);
        $tokenParts = explode(".", $token);  
        $tokenHeader = base64_decode($tokenParts[0]);
        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtHeader = json_decode($tokenHeader);
        $jwtPayload = json_decode($tokenPayload);
        $email = $jwtPayload->username;

        $em = $doctrine->getManager();
        $query = "SELECT i FROM App\Entity\User i WHERE i.email = :email";
        $query = $em->createQuery($query);
        $query->setParameter('email', $email);
        $user = $query->getResult();
        $user_id = 0;
        if (count($user) > 0) {
            $user_id = $user[0]->getId();
        }
        return $user_id;
    }

    public function uploadImage(ManagerRegistry $doctrine, Request $request): Response
    {
        $image = $request->files->get('image');
        $source = $request->request->get('source');
        $tag = $request->request->get('tag');
        $provider = $request->request->get('provider');
        $baseurl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
        if ($image != NULL) {
            $fileExtension = $image->getClientOriginalExtension();
            $allowedfileExtensions = array('jpg', 'gif', 'png');
            if (in_array($fileExtension, $allowedfileExtensions)) {
                $fileExtension = $image->getClientOriginalExtension();
                $fileName = $image->getClientOriginalName();
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $image->move('../uploads', $newFileName);
                $this->uploadFile($doctrine, $provider, $tag, $baseurl.'/'.$newFileName);
                $message = 'success uploaded successfully';
            } else {
                $message = 'error invalid format';
            }
        } else if ($source != '') {
            $newFileName = md5(uniqid()) . '.png';
            $image =  file_get_contents($source);
            $result = file_put_contents($this->getParameter('kernel.project_dir').'/uploads/'.$newFileName, $image);
            if ($image != NULL) {
                $this->uploadFile($doctrine, $provider, $tag, $baseurl.'/'.$newFileName);
                $message = 'success uploaded successfully';
            } else {
                $message = 'File downlaod error';
            }
        }
        return $this->json(['message' => $message]);
    }

    function uploadFile($doctrine, $provider, $tag, $fileName) {
        $entityManager = $doctrine->getManager();
        $images = new Images();
        $images->setImagePath($fileName);
        $images->setTags($tag);
        $images->setProvider($provider);
        $datetime = \DateTimeImmutable::createFromMutable(new \DateTime());
        $images->setCreatedAt($datetime);
        $entityManager->persist($images);
        $entityManager->flush();
        return True;
    }

    public function setUserImages(ManagerRegistry $doctrine, Request $request): Response
    {
        $user_id = $request->request->get('user_id');
        $image_id = $request->request->get('image_id');
        $entityManager = $doctrine->getManager();
        $userimages = new UserImages();
        $userimages->setUserId($user_id);
        $userimages->setImageId($image_id);
        $entityManager->persist($userimages);
        $entityManager->flush();
        return $this->json('success');
    }

}