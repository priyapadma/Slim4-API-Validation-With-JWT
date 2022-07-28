<?php

declare(strict_types=1);

namespace App\Application\Helpers;

class UserHelper {
   
   public function createUser($db, $data){	
        $first_name = isset($data['first_name']) ? $data["first_name"] : '';
        $last_name = isset($data['last_name']) ? $data["last_name"] : '';
        $email = isset($data['email']) ? $data["email"] : '';
        $password = isset($data['password']) ? $data["password"] :  '';
        $confirm_password = isset($data['confirm_password']) ? $data["confirm_password"] :  '';
		$industry = isset($data['industry']) ? $data["industry"] :  '';
		$job_title = isset($data['job_title']) ? $data["job_title"] :  '';
		$country = isset($data['country']) ? $data["country"] :  '';
		$company_size = isset($data['company_size']) ? $data["company_size"] :  '';
		$brand_id = isset($data['brand_id']) ? $data["brand_id"] :  '';
		$password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
		$date = date('Y-m-d h:i:s');
		$marketing_optin = isset($data['marketing_optin']) ? $data['marketing_optin'] : '';
		$third_party_optin = isset($data['third_party_optin']) ? $data['third_party_optin'] : '';
		$sql = $db->prepare("INSERT INTO users (brand_id, first_name, last_name, email, password, industry, job_ttl, comp_size, registered_on) 
		SELECT * FROM (SELECT :brand_id AS brand_id,:first_name AS first_name,:last_name AS last_name,:email AS email,:password AS password,:industry AS industry,:job_title AS job_ttl,:company_size AS comp_size,:registered_on AS registered_on) AS tmp 
		WHERE NOT EXISTS (SELECT email FROM users WHERE email = :email) LIMIT 1");
		$sql->execute(array(':brand_id' => $brand_id, ':first_name' => $first_name, ':last_name' => $last_name, ':email' => $email, ':password' => $password, ':industry' => $industry, ':job_title' => $job_title, ':company_size' => $company_size, ':registered_on' => date('Y-m-d h:i:s')));
		//$sql->debugDumpParams();
		$count = $sql->rowCount();
		$lastinserid = $db->lastInsertId();
		if ($count && $lastinserid) {
			$user_dt_sql = $db->prepare("INSERT INTO user_details (user_id, brand_id, marketing_optin, third_party_optin) VALUES (:user_id, :brand_id, :marketing_optin, :third_party_optin)");
			$user_dt_sql->execute(array(':user_id' => $lastinserid, ':brand_id' => $brand_id, ':marketing_optin' => $marketing_optin, ':third_party_optin' => $third_party_optin));
            $count = $sql->rowCount();
		}

	   return $count;
   }
   
   public function updateAccount($db, $data, $id) {
        $first_name = isset($data['first_name']) ? $data["first_name"] : '';
        $last_name = isset($data['last_name']) ? $data["last_name"] : '';
        $email = isset($data['email']) ? $data["email"] : '';
        $password = isset($data['password']) ? $data["password"] :  '';
        $confirm_password = isset($data['confirm_password']) ? $data["confirm_password"] :  '';
		$industry = isset($data['industry']) ? $data["industry"] :  '';
		$job_title = isset($data['job_title']) ? $data["job_title"] :  '';
		$country = isset($data['country']) ? $data["country"] :  '';
		$company_size = isset($data['company_size']) ? $data["company_size"] :  '';
		$comp_name = isset($data['comp_name']) ? $data["comp_name"] :  '';
		$brand_id = isset($data['brand_id']) ? $data["brand_id"] :  '';
		$user_id = $id;
		$marketing_optin = isset($data['marketing_optin']) ? "TRUE" : '';
		$third_party_optin = isset($data['third_party_optin']) ? "TRUE" : '';
		$sql = $db->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name, industry = :industry, job_ttl = :job_ttl, comp_size = :comp_size, comp = :comp	 
		WHERE user_id = :user_id AND brand_id = :brand_id");
		$sql->execute(array(':first_name' => $first_name, ':last_name' => $last_name, ':industry' => $industry, ':job_ttl' => $job_title, ':comp_size' => $company_size, 'comp' => $comp_name,  'user_id' => $user_id, 'brand_id' => $brand_id));
		$count = $sql->rowCount();
		if ($count) {
			$user_dt_sql = $db->prepare("UPDATE user_details SET country = :country, dob = :dob, gender = :gender, phone = :phone, marketing_optin = :marketing_optin, third_party_optin = :third_party_optin WHERE user_id = :user_id AND brand_id = :brand_id");
			$user_dt_sql->execute(array(':country' => $country, ':dob' => $dob, ':gender' => $gender, ':phone' => $phone, ':marketing_optin' => $marketing_optin, ':third_party_optin' => $third_party_optin, ':user_id' => $user_id, ':brand_id' => $brand_id));
            $count = $sql->rowCount();
		}

	   return $count;    
   }
   
}
