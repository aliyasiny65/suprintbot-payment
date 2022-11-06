<?php
include "api/config.php";
$get_payment_id = $_GET['payment_id'];

$payment_q = $db->query("SELECT * FROM payments WHERE payment_id = '{$get_payment_id}'",PDO::FETCH_ASSOC);
$payment_q_data_query = $payment_q->fetch(PDO::FETCH_ASSOC);
$payment_q_count = $payment_q -> rowCount();

if($payment_q_count > 0 && $payment_q_data_query['status'] == 0) {
$get_user_username = $payment_q_data_query['username'];
$get_user_avatar = $payment_q_data_query['avatar'];
$get_amount = $payment_q_data_query['amount'];
$get_user_discord_user_id = $payment_q_data_query['user_id'];

$get_card_number = str_replace(" ", "",$_POST['card_number']);
$get_card_expdate = $_POST['month']."/".$_POST['year'];
$get_card_cvv = $_POST['card_cvv'];

//START NORMAL PAY
if(isset($_POST['pay'])) {
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"https://bank.nomee6.xyz/api/v1/create_payment");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,
            "client_id=$nomee6_bank_app_client_id&secret=$nomee6_bank_app_secret&card_number=$get_card_number&card_expire=$get_card_expdate&card_cvv=$get_card_cvv&amount=$get_amount");
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $server_output = curl_exec($ch);
  
        curl_close ($ch);
  
  		$json_response = json_decode($server_output);
  		
  		$_pay_status = $json_response->status_message;
  		$_pay_code = $json_response->data;
  		if($_pay_status == "Error: User money not enough.") {
        	echo '<script>alert("Kartta ki paranız yeterli değil!")</script>';
        } else if($_pay_status == "Error: User card not found.") {
        	echo '<script>alert("Kart bilgileriniz geçerli değil!")</script>';
        } else if($_pay_status == "success") {
			$save_card_check = $_POST['save_card'];
			if($save_card_check == "on") {
				$generate_card_id = openssl_random_pseudo_bytes(40);
				$generate_card_id = bin2hex($generate_card_id);
				
				$save_card_q = $db->prepare("INSERT INTO cards SET
					card_id = ?,
					user_id = ?,
            	    card_number = ?,
					card_exp_date = ?,
					card_cvv = ?");
				$save_card_q_query = $save_card_q->execute(array(
				     $generate_card_id, $get_user_discord_user_id, $get_card_number, $get_card_expdate, $get_card_cvv
				));
				$save_card_q_query;
			}
          	$update_payment_status_q = $db->prepare("UPDATE payments SET
				status = :new_status,
				pay_code = :new_pay_code
				WHERE payment_id = :payment_id");
			$update_payment_status_q_query = $update_payment_status_q->execute(array(
				"new_status" => "3d",
				"new_pay_code" => $_pay_code,
			    "payment_id" => $get_payment_id
			));
			
            if($update_payment_status_q_query) {
			    header("Refresh:0 url=https://api.nomee6.xyz/v2/pay?pay_code=$_pay_code");
            } else {
                echo "Bir hata oluştu!";
            }   
        } else {
        	echo '<script>alert("Ödemeniz banka kuruluşunuz tarafından reddedildi!")</script>';
        }
}
//END NORMAL PAY
//START SAVED CARD PAY
if(isset($_POST['saved_card_pay'])) {
		$_get_card_id = $_POST['saved_cards'];
		$_card_q = $db->query("SELECT * FROM cards WHERE card_id = '{$_get_card_id}'",PDO::FETCH_ASSOC);
		$_card_q_data_query = $_card_q->fetch(PDO::FETCH_ASSOC);
		$_get_saved_card_number = $_card_q_data_query['card_number'];
		$_get_saved_card_expdate = $_card_q_data_query['card_exp_date'];
		$_get_saved_card_cvv = $_card_q_data_query['card_cvv'];
		
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"https://bank.nomee6.xyz/api/v1/create_payment");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,
            "client_id=$nomee6_bank_app_client_id&secret=$nomee6_bank_app_secret&card_number=$_get_saved_card_number&card_expire=$_get_saved_card_expdate&card_cvv=$_get_saved_card_cvv&amount=$get_amount");
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $server_output = curl_exec($ch);
  
        curl_close ($ch);
  
  		$json_response = json_decode($server_output);
  		
  		$_pay_status = $json_response->status_message;
  		$_pay_code = $json_response->data;
  		if($_pay_status == "Error: User money not enough.") {
        	echo '<script>alert("Kartta ki paranız yeterli değil!")</script>';
        } else if($_pay_status == "Error: User card not found.") {
        	echo '<script>alert("Kart bilgileriniz geçerli değil!")</script>';
        } else if($_pay_status == "success") {
			$save_card_check = $_POST['save_card'];
          	$update_payment_status_q = $db->prepare("UPDATE payments SET
				status = :new_status,
				pay_code = :new_pay_code
				WHERE payment_id = :payment_id");
			$update_payment_status_q_query = $update_payment_status_q->execute(array(
				"new_status" => "3d",
				"new_pay_code" => $_pay_code,
			    "payment_id" => $get_payment_id
			));
			
            if($update_payment_status_q_query) {
			    header("Refresh:0 url=https://api.nomee6.xyz/v2/pay?pay_code=$_pay_code");
            } else {
                echo "Bir hata oluştu!";
            }   
        } else {
        	echo '<script>alert("Ödemeniz banka kuruluşunuz tarafından reddedildi!")</script>';
        }
}
//END SAVED CARD PAY

	
} else {
	header("Location: https://suprintbot.xyz");
}
?>

<!doctype html>
<html lang="tr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Suprint Bot Payments</title>
    <!-- CSS files -->
    <link href="https://egitim.nomee6.xyz/dist/css/tabler.min.css" rel="stylesheet" />
    <link href="https://egitim.nomee6.xyz/dist/css/tabler-flags.min.css" rel="stylesheet" />
    <link href="https://egitim.nomee6.xyz/dist/css/tabler-payments.min.css" rel="stylesheet" />
    <link href="https://egitim.nomee6.xyz/dist/css/tabler-vendors.min.css" rel="stylesheet" />
    <link href="https://egitim.nomee6.xyz/dist/css/demo.min.css" rel="stylesheet" />
</head>

<body>
    <div class="page">
        <!-- Navbar -->
        <header class="navbar navbar-expand-md navbar-light d-print-none">
            <div class="container-xl">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                    <a href=".">
                        <img src="https://suprintbot.xyz/assets/images/logo.png" width="110" height="32" alt="SuprintBot" class="navbar-brand-image">
                    </a>
                </h1>
                <div class="navbar-nav flex-row order-md-last">
                    <div class="d-none d-md-flex">
                        <a href="https://pay.suprintbot.xyz/pay?payment_id=<?php echo $get_payment_id; ?>&theme=dark" class="nav-link px-0 hide-theme-dark" title="Koyu Temaya geç" data-bs-toggle="tooltip" data-bs-placement="bottom">
                            <!-- Download SVG icon from http://tabler-icons.io/i/moon -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z" />
                            </svg>
                        </a>
                        <a href="https://pay.suprintbot.xyz/pay?payment_id=<?php echo $get_payment_id; ?>&theme=light" class="nav-link px-0 hide-theme-light" title="Açık Temaya geç" data-bs-toggle="tooltip" data-bs-placement="bottom">
                            <!-- Download SVG icon from http://tabler-icons.io/i/sun -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <circle cx="12" cy="12" r="4" />
                                <path d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7" />
                            </svg>
                        </a>
                    </div>
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
                            <span class="avatar avatar-sm" style="background-image: url(<?php echo $get_user_avatar; ?>)"></span>
                            <div class="d-none d-xl-block ps-2">
                                <div><?php echo $get_user_username; ?></div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </header>
        <div class="page-wrapper">
            <!-- Page header -->
            <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            <h2 class="page-title">
                                Ödeme sayfası
                            </h2>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Page body -->
            <div class="page-body">
                <div class="container-xl">
                    <div class="card">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Kayıtlı Ödeme Yöntemleri</label>
                                <form method="POST" action="" class="form-selectgroup form-selectgroup-boxes d-flex flex-column">
									<?php
             							$sql = "SELECT * FROM cards WHERE user_id = '$get_user_discord_user_id'";
             							$result = mysqli_query($conn, $sql);
             							while($row = mysqli_fetch_array($result)){
											$_Get_card_number = $row['card_number'];
											$_Get_card_id = $row['card_id'];
											$_card_number_4_digits = substr($_Get_card_number, -4);
											
											echo '<label class="form-selectgroup-item flex-fill">
                                        <input type="radio" name="saved_cards" value="'.$_Get_card_id.'" class="form-selectgroup-input">
                                        <div class="form-selectgroup-label d-flex align-items-center p-3">
                                            <div class="me-3">
                                                <span class="form-selectgroup-check"></span>
                                            </div>
                                            <div>
                                                <span class="payment payment-xs"><img src="https://nomee6.xyz/assets/pp.png" class="payment-xs"></span>
                                                <strong>'.$_card_number_4_digits.'</strong> ile biten Nomee6 Card
                                            </div>
                                        </div>
                                    </label>';
			 							};
									$_user_cards_q_ = $db->query("SELECT * FROM cards WHERE user_id = '{$get_user_discord_user_id}'",PDO::FETCH_ASSOC);
									$_user_cards_q_count = $_user_cards_q_ -> rowCount();
									
									if($_user_cards_q_count > 0) {
										echo '<button name="saved_card_pay" class="btn btn-primary w-100">
                                        Öde
                                    </button>';
									}
									?>
                                </form>
                            </div>
                        </div>
                    </div>
                    <br>
                    <form method="POST" action="" class="card">
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="form-label">Kart Numarası</div>
                                <input type="text" class="form-control" name="card_number" data-mask="0000 0000 0000 0000" data-mask-visible="true" placeholder="0000 0000 0000 0000" autocomplete="off" required/>
                            </div>
                            <div class="row">
                                <div class="col-8">
                                    <div class="mb-3">
                                        <label class="form-label">Bitiş Tarihi</label>
                                        <div class="row g-2">
                                            <div class="col">
                                                <select name="month" class="form-select" required>
                                                    <option value="1">1</option>
                                                    <option value="2">2</option>
                                                    <option value="3">3</option>
                                                    <option value="4">4</option>
                                                    <option value="5">5</option>
                                                    <option value="6">6</option>
                                                    <option value="7">7</option>
                                                    <option value="8">8</option>
                                                    <option value="9">9</option>
                                                    <option value="10">10</option>
                                                    <option value="11">11</option>
                                                    <option value="12">12</option>
                                                </select>
                                            </div>
                                            <div class="col">
                                                <select name="year" class="form-select" required>
                                                    <option value="23">2023</option>
                                                    <option value="24">2024</option>
                                                    <option value="25">2025</option>
                                                    <option value="26">2026</option>
                                                    <option value="27">2027</option>
                                                    <option value="28">2028</option>
                                                    <option value="29">2029</option>
                                                    <option value="30">2030</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="mb-3">
                                        <div class="form-label">CVV</div>
                                        <input name="card_cvv" type="number" class="form-control" maxlength="3" max="999" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <label class="form-check">
                                    <input name="save_card" class="form-check-input" type="checkbox" checked>
                                    <span class="form-check-label">Kartımı daha sonraki alışverişlerim için sakla</span>
                                </label>
                                <button name="pay" class="btn btn-primary w-100">
                                    Öde
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <footer class="footer footer-transparent d-print-none">
                <div class="container-xl">
                    <div class="row text-center align-items-center flex-row-reverse">
						<div class="col-lg-auto ms-lg-auto">
              				<ul class="list-inline list-inline-dots mb-0">
                				<li class="list-inline-item">
                 				 Hosted by <a href="https://cloud.nomee6.xyz" target="_blank" class="link-secondary" rel="noopener">
                    				<svg xmlns="http://www.w3.org/2000/svg" class="icon text-pink icon-filled icon-inline" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19.5 12.572l-7.5 7.428l-7.5 -7.428m0 0a5 5 0 1 1 7.5 -6.566a5 5 0 1 1 7.5 6.572" /></svg>Nomee6 Cloud
                				  </a>
                				</li>
              				</ul>
            			</div>
                        <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                            <ul class="list-inline list-inline-dots mb-0">
                                <li class="list-inline-item">
                                    Copyright &copy; 2022
                                    <a href="." class="link-secondary">SuprintBot</a>.
                                    Tüm Hakları saklıdır.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <script src="https://egitim.nomee6.xyz/dist/js/tabler.min.js" defer></script>
    <script src="https://egitim.nomee6.xyz/dist/js/demo.min.js" defer></script>
</body>

</html>
