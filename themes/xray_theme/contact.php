<?php
include "header.php";

$addrValue = isset($addr) ? (string) $addr : '';
$addr2Value = isset($addr2) ? (string) $addr2 : '';
$emailValue = isset($email) ? (string) $email : '';
$email2Value = isset($email2) ? (string) $email2 : '';
$phoneValue = isset($phone) ? (string) $phone : '';
$urlhValue = isset($urlh) ? (string) $urlh : '';
?>

<?php
$addrDisplay = nl2br(htmlspecialchars($addrValue, ENT_QUOTES, 'UTF-8'));
$addr2Display = nl2br(htmlspecialchars($addr2Value, ENT_QUOTES, 'UTF-8'));
?>



<script src="../../www.google.com/recaptcha/api.js"></script>
<link rel="stylesheet" href="../../use.typekit.net/wvb2qfz.css">

<section class="background-grey p-b-0 rctmob "
    style="background-image:url('../static/images/nycbs_november_ss.png');background-repeat: no-repeat; background-size: cover; background-position: 0% 0%;height:260px;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">

                <h1 style="color: #fff;
									font-family: myriad-pro-semi-condensed, sans-serif;
									font-weight: 900;
									font-style: normal;margin-bottom: 0px;font-size:2.5em; color:rgb(103,42,133);">Need to reach us?
                </h1>

                <p style="color: #fff; font-family: myriad-pro, sans-serif;
font-weight: 400;
font-style: normal;color:rgb(103,42,133);">Contact us or book an appointment.</p>


            </div>
        </div>

    </div>
    </div>
</section>
<section class="p-t-0">
    <div class="container">
        <div class="row">


            <div class="col-lg-12">
                <div class="p-t-10 p-b-50 m-t-5 m-b-40 p-20"
                    style="border: 1px solid #ccc; padding-left:10px;padding-right:10px; background-color: rgba(187,198,226,.05);">






                    <center>
                        <h4 class="text-uppercase">Contact Us</h4>
                        <p style="font-size:11pt;">For Support, inquiries or to book an appointment.</p>
                    </center>
                    <hr>
                    <div class="m-t-30">
                        <div class="row m-t-20 m-b-10" style="font-size:110%;padding:10px;">

                            <div class="col-lg-6">
                                <h3>Canada Center</h3>







                                <div class="col-lg-12">
                                    <h4><strong><a href="#"
                                                style="color:rgb(110,85,196);"><?php echo htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8'); ?></a></strong>
                                    </h4>
                                    <address>
                                        <?php echo $addrDisplay; ?><br>
                                        <abbr title="Phone">P:</h4> <a
                                                href="tel:<?php echo htmlspecialchars($phoneValue, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($phoneValue, ENT_QUOTES, 'UTF-8'); ?></a><br>
                                    </address>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <h3>USA Center</h3>







                                <div class="col-lg-12">
                                    <h4><strong><a href="#"
                                                style="color:rgb(110,85,196);"><?php echo htmlspecialchars($email2Value, ENT_QUOTES, 'UTF-8'); ?></a></strong>
                                    </h4>
                                    <address>
                                        <?php echo $addr2Display; ?><br>
                                        <abbr title="Phone">P:</h4> <a
                                                href="tel:<?php echo htmlspecialchars($phoneValue, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($phoneValue, ENT_QUOTES, 'UTF-8'); ?></a><br>
                                    </address>
                                </div>
                            </div>


                        </div>
                    </div>
                    <br>
                    <hr>
                    <center>
                        <h3><br>Billing Department</h3>
                        <h4><strong><a href="#" style="color:rgb(110,85,196);">billing@<?php echo htmlspecialchars($urlhValue, ENT_QUOTES, 'UTF-8'); ?></a></strong>
                        </h4>
                        <address>

                            <abbr title="Phone">P:</h4> <a
                                    href="tel:<?php echo htmlspecialchars($phoneValue, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($phoneValue, ENT_QUOTES, 'UTF-8'); ?></a><br>
                        </address>
                    </center>

                </div>
            </div>


        </div>
    </div>
</section>


<?php
include "footer.php";
?>