<?php
include "../header.php"
?>

<!--<body>







    
<div class="body-inner">
    
<div id="topbar" class="d-xl-block d-lg-block">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <ul class="top-menu-box top-menu" style="float:right;">

                    <li><a href="tel:<?php echo $phone; ?>"><?php echo $phone; ?></a></li>

                </ul>
            </div>
            <div class="d-none" style="display:none;">
                <div class="social-icons social-icons-colored-hover">
                    <ul>

                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="container">
    
<header id="header" data-transparent="true" data-fullwidth="true" class="light submenu-light"
    style="background-color: #FFF;">
    <div class="header-inner">
        <div class="container">
            
<div id="logo">
    <a href="../index.php"><span class=""><img src="../static/images/NY_Imaging_Specialists.png"
                style="max-width:250px;" alt="<?php echo $name; ?>  Logo" /></span></a>
</div>




<div class="header-extras">
    <ul>

        <li class="d-none d-xl-block d-lg-block">
            <a href="<?php echo $url; ?>/contact.php" class="btn btn-rounded btn-sm"
                style="background-color: rgb(76,66,135);border-color: rgb(76,66,135); color: #FFF;">Make
                Appointment</a>
        </li>

    </ul>
</div>





<div id="mainMenu-trigger">
    <button class="lines-button x"> <span class="lines"></span></button>
</div>



<div id="mainMenu" class="light">
    <div class="container">
        <nav>
            <ul>
                <li><a href="../locations.php">Locations</a></li>
                <li><a href="../people.php">Our Team</a></li>


                <li>
                    <a href="../our-equipment/index.php">Services</a>
                    <ul class="dropdown-menu">
                        <li class=""><a href="../dexa-scan/index.php">Bone Density</a></li>
                        <li class=""><a href="../computed-tomography-ct/index.php">Computed
                                Tomography (CT)</a></li>

                        <li class=""><a
                                href="../main-interventional-radiology-oncology/index.php">Interventional
                                Radiology &amp; Oncology</a></li>
                        <li class=""><a href="../lung-screening/index.php">Lung Screening</a>
                        </li>
                        <li class=""><a href="../mammo/index.php">Mammography</a></li>
                        <li class=""><a href="../mri/index.php">MRI</a></li>
                        <li class=""><a href="../fdg-pet-ct-scan/index.php">PET/CT Scan</a></li>
                        <li class=""><a
                                href="../radionuclide-therapies-2/index.php">Radionuclide
                                Therapies</a></li>
                        <li class=""><a
                                href="../transthoracic-echocardiogram-tte/index.php">Transthoracic
                                Echocardiogram (TTE)</a></li>
                        <li class=""><a href="../ultrasound-2/index.php">Ultrasound</a></li>

                    </ul>
                </li>




                <li class="dropdown">
                    <a href="#">About</a>
                    <ul class="dropdown-menu">
                        <li class=""><a
                                href="../insurance-billing-information/index.php">Accepted
                                Insurance</a></li>
                        <li class=""><a href="../about-us/index.php">About Us</a></li>
                        <li class=""><a href="../hipaa-notice-of-privacy-practices.php">HIPAA
                                and Compliance</a></li>
                        <li class=""><a href="../ada-statement/index.php">ADA Statement</a></li>
                        <li class=""><a href="../covid-policy/index.php">COVID-19 POLICY</a>
                        </li>
                        <li class=""><a href="../nassau-county/index.php">Nassau County</a></li>
                        <li class=""><a href="../news.php">News</a></li>
                    </ul>
                </li>
                <li><a href="index.php">Payment</a></li>
                <li class="dropdown">
                    <a href="#">Portals</a>
                    <ul class="dropdown-menu">

                        <li class=""><a
                                href="https://accounts.flatiron.com/account/login/?ReturnUrl=%2Fconnect%2Fauthorize%2Fcallback%3Fclient_id%3Dfh.patient_portal%26nonce%3D17h7jA8gPTj9SJMK%26redirect_uri%3Dhttps%253A%252F%252Fcarespaceportal.com%252Fcb%26response_type%3Dcode%26scope%3Dopenid%2520profile%26state%3DxcuQzpriokHxgTi1">Patients</a>
                        </li>

                        <li class="dropdown-submenu"><span class="dropdown-arrow"></span><a
                                href="#">Physicians</a>
                            <ul class="dropdown-menu">
                                <li class=""><a href="../infinitt/index.php">INFINITT</a></li>

                                <li><a
                                        href="https://scriptsender.nycancer.com/">ScriptSender</a>
                                </li>
                            </ul>
                        </li>



                    </ul>
                </li>

            </ul>

        </nav>
    </div>
</div>

</div>
</div>
</header>
</div>
<!-- end: Header -->


<section id="page-content">
    <div class="container">
        <h1>Payment</h1>
        <br>
        <p style="font-size: 18px; font-weight: bold; text-align: left;">For your convenience, you can make a
            payment by clicking on the tab below. <br /><br /> We accept Visa, Mastercard, American Express, and
            Discover:<br /><br /></p>
        <center>
            <form action="https://simplecheckout.authorize.net/payment/CatalogPayment.aspx" method="post"
                name="PrePage"><input type="hidden" name="LinkId"
                    value="af1c975c-3109-49de-9a9c-cf2a490a4538" /> <input class="btn"
                    style="left: 0px; background-color: #583b92; border-color: #583b92;" type="submit"
                    value="Click here to make a bill payment" /></form>
        </center>
    </div>
</section>
<div class="call-to-action p-t-100 p-b-100  mb-0 call-to-action-dark" style="background-color:rgb(110,85,196);">
    <div class="container">
        <div class="row">
            <div class="col-lg-9">
                <h3>
                    HOW CAN WE HELP YOU?
                </h3>
                <p>
                    Our mission at <?php echo $name; ?> is to provide the highest-quality advanced imaging in
                    a patient-centered and compassionate environment, with the comfort and convenience of being
                    close to home.<br>
                    <br>
                    To schedule an appointment by phone call<?php echo $phone; ?> .
                </p>
            </div>
            <div class="col-lg-3"><a class="btn btn-light" href="<?php echo $url; ?>/contact.php"
                    style="margin-top:70px;">Request Appointment Now</a></div>
        </div>
    </div>
</div>


<?php
include "../footer.php"
?>