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
                <li><a href="../payment/index.php">Payment</a></li>
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


<section id="page-title" class="text-light"
    style="background-image:url(../media/location_pic/nyimagingnostrand.png); background-size: cover; background-position: center center;">
    <div class="container">

        <div class="page-title">
            <h1 style="padding-top:175px;padding-bottom:150px;"><span
                    style="font-size:34pt;background-color: rgba(0,0,0,.3);padding:10px;line-height:73px;">NY
                    Imaging Brooklyn</span></h1>
        </div>
    </div>
</section>

<div class="container">

    <div class="row">
        <div class="col-lg-6">
            <br>
            <a class="btn" style="background-color:rgb(88,59,146);border-color:rgb(88,59,146);"
                href="<?php echo $url; ?>/contact.php">Request Appointment Online</a>
            <br>
            <br>
            <div class="card">
                <div class="card-body">
                    <h3>Phone: <a href="tel://<?php echo $phone; ?> "><?php echo $phone; ?> </a></h3>
                    <h3>Fax: 631-663-2034</h3>
                    <h3>Address<h3>
                            <h5><a href="https://www.google.com/maps/dir/Current+Location/40.630160,-73.948010"
                                    target="none">Directions</a></h5>
                            <p><a href="https://maps.google.com/?q=2236%20Nostrand%20Ave%2C%20Brooklyn%2C%20NY%2011210"
                                    target="none">2236 Nostrand Ave, Brooklyn, NY 11210</a></p>
                            <br>

                            <img alt="" src="../media/location_pic/nyimagingnostrand.png"
                                style="max-width:200px;" class="img-fluid img-thumbnail">

                </div>
            </div>



        </div>
        <div class="col-lg-6">
            <br>
            <br>
            <br>
            <br>
            <!-- Google Map -->


            <!-- end: Google Map -->
            <div class="card">
                <div class="card-body">
                    <div class="map" data-latitude="40.630160" data-longitude="-73.948010" data-style="light"
                        data-info="Location Map for <?php echo $name; ?>  Brooklyn"
                        style="max-height:350px;min-height:330px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h4 class="" style="padding:10px;">Services Offered</h4>
            <div class="row center">



                <div class="col-lg-3">
                    <div class="team-member">

                        <div class="team-desc">
                            <p>Breast Imaging</p>
                        </div>

                    </div>
                </div>



                <div class="col-lg-3">
                    <div class="team-member">

                        <div class="team-desc">
                            <p>CT</p>
                        </div>

                    </div>
                </div>



                <div class="col-lg-3">
                    <div class="team-member">

                        <div class="team-desc">
                            <p>MRI</p>
                        </div>

                    </div>
                </div>



                <div class="col-lg-3">
                    <div class="team-member">

                        <div class="team-desc">
                            <p>Mammography</p>
                        </div>

                    </div>
                </div>



                <div class="col-lg-3">
                    <div class="team-member">

                        <div class="team-desc">
                            <p>PET/CT</p>
                        </div>

                    </div>
                </div>



                <div class="col-lg-3">
                    <div class="team-member">

                        <div class="team-desc">
                            <p>Ultrasound</p>
                        </div>

                    </div>
                </div>



                <div class="col-lg-3">
                    <div class="team-member">

                        <div class="team-desc">
                            <p>Ultrasound - Breast</p>
                        </div>

                    </div>
                </div>



            </div>
        </div>
    </div>


    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>


</div>


<?php
include "../footer.php"
?>