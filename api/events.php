<?php

header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods:*');
header('Access-Control-Allow-Headers:*');
header('Access-Control-Allow-Post:*');

define("JWT_KEY", "nrs-1234");

date_default_timezone_set("UTC") ;

require_once("JWT.php");

$data = json_decode(file_get_contents("php://input"), TRUE);

$token = $data['token'];

if (!empty($token)) {
	
	$arrUserData = validate_id_token($token);
	//print_r($arrUserData);
	//print_r($arrUserData->user->email);
	
	if($arrUserData->user->email!="" && $arrUserData->user->password!="") {
	
		include (dirname(__FILE__).'/db_connect.php');

		$sql = "SELECT 	users.*,
						(select companies.companyName from companies WHERE users.clientID = companies.companyID) as companyName,
						(select companies.accountStatus from companies WHERE users.clientID = companies.companyID) as accountStatus,
						(select companies.passwordFrequency from companies WHERE users.clientID = companies.companyID) as passwordFrequency					
				FROM 	users 
				WHERE 	userName ='".mysqli_real_escape_string($conn,$arrUserData->user->email)."' AND
						userPass ='".mysqli_real_escape_string($conn,md5($arrUserData->user->password))."'";
						
		
		$rs=mysqli_query($conn, $sql) or die(mysqli_error($conn).$sql);
		
		if (mysqli_num_rows($rs)==1) { // rudimentary SUCCESS
		
			$rc=mysqli_fetch_assoc ($rs);

	

			$sql = "SELECT 	events.eventID, events.eventRef, events.eventTitle, events.eventDate, events.userID, events.companyID, events.speakers, 	
							(	SELECT count(attendees.attendeeID) as total from attendees WHERE attendees.eventID=events.eventID) as total,
							(select companies.companyName from companies WHERE events.companyID = companies.companyID) as companyName,
							(SELECT count(attendees.attendeeID) as total from attendees WHERE attendees.eventID=events.eventID AND attendees.invited='Yes') as invited ,
							(select concat(users.firstName,' ',users.lastName) from users WHERE events.userID = users.userID) as userFullName
					FROM events ".
					(($rc["userGroup"]>2) ? "WHERE `events`.userID='".$rc["userID"]."'": "").
					"GROUP BY `events`.eventID ";
		
					
			$arr_tableData = array();

			$rs=mysqli_query($conn, $sql) or die(mysqli_error($conn).$sql);
			while($rc=mysqli_fetch_assoc ($rs)) {	
				$rc["speakers"] = json_decode($rc["speakers"],1);
				$arr_tableData[] = $rc;
				
			}
			 
			 echo "[";
			 $data = "";
			 
				foreach ($arr_tableData as $key => $objectdata) {
				
					$data.= json_encode($objectdata).",";
				}
			 
			echo trim($data," ,")."]";

			
		}
		mysqli_close($conn);
	}
} else {
	echo "NO PAYLOAD";
}
exit();



?>