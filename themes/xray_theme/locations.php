<?php
include "header.php"
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
    <a href="index.php"><span class=""><img src="static/images/NY_Imaging_Specialists.png"
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
                <li><a href="locations.php">Locations</a></li>
                <li><a href="people.php">Our Team</a></li>


                <li>
                    <a href="our-equipment/index.php">Services</a>
                    <ul class="dropdown-menu">
                        <li class=""><a href="dexa-scan/index.php">Bone Density</a></li>
                        <li class=""><a href="computed-tomography-ct/index.php">Computed
                                Tomography (CT)</a></li>

                        <li class=""><a
                                href="main-interventional-radiology-oncology/index.php">Interventional
                                Radiology &amp; Oncology</a></li>
                        <li class=""><a href="lung-screening/index.php">Lung Screening</a></li>
                        <li class=""><a href="mammo/index.php">Mammography</a></li>
                        <li class=""><a href="mri/index.php">MRI</a></li>
                        <li class=""><a href="fdg-pet-ct-scan/index.php">PET/CT Scan</a></li>
                        <li class=""><a href="radionuclide-therapies-2/index.php">Radionuclide
                                Therapies</a></li>
                        <li class=""><a
                                href="transthoracic-echocardiogram-tte/index.php">Transthoracic
                                Echocardiogram (TTE)</a></li>
                        <li class=""><a href="ultrasound-2/index.php">Ultrasound</a></li>

                    </ul>
                </li>




                <li class="dropdown">
                    <a href="#">About</a>
                    <ul class="dropdown-menu">
                        <li class=""><a href="insurance-billing-information/index.php">Accepted
                                Insurance</a></li>
                        <li class=""><a href="about-us/index.php">About Us</a></li>
                        <li class=""><a href="hipaa-notice-of-privacy-practices.php">HIPAA and
                                Compliance</a></li>
                        <li class=""><a href="ada-statement/index.php">ADA Statement</a></li>
                        <li class=""><a href="covid-policy/index.php">COVID-19 POLICY</a></li>
                        <li class=""><a href="nassau-county/index.php">Nassau County</a></li>
                        <li class=""><a href="news.php">News</a></li>
                    </ul>
                </li>
                <li><a href="payment/index.php">Payment</a></li>
                <li class="dropdown">
                    <a href="#">Portals</a>
                    <ul class="dropdown-menu">

                        <li class=""><a
                                href="https://accounts.flatiron.com/account/login/?ReturnUrl=%2Fconnect%2Fauthorize%2Fcallback%3Fclient_id%3Dfh.patient_portal%26nonce%3D17h7jA8gPTj9SJMK%26redirect_uri%3Dhttps%253A%252F%252Fcarespaceportal.com%252Fcb%26response_type%3Dcode%26scope%3Dopenid%2520profile%26state%3DxcuQzpriokHxgTi1">Patients</a>
                        </li>

                        <li class="dropdown-submenu"><span class="dropdown-arrow"></span><a
                                href="#">Physicians</a>
                            <ul class="dropdown-menu">
                                <li class=""><a href="infinitt/index.php">INFINITT</a></li>

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


<section class="no-padding">
    <!-- Google Map 
            <div class="map" data-latitude="-37.817240" data-longitude="144.955826" data-style="light" data-info="Hello from &lt;br&gt; Inspiro Themes" data-height-lg="500" data-height-xs="200" data-height-sm="300"></div>-->
    <!--<!-- src="https://www.google.com/maps/d/embed?mid=15lG0KBXNNeCLLTEmXy-V7QKSpnxnaEX4" width="640" height="480">-->-->
    <!-- end: Google Map -->
    <div id="google-map-multiple" style="height: 400px; margin-bottom: 20px;" class="img rounded"></div>
    <br>
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="">Locations</h1>
                <!--
                        <div class="input-group mb-3">
                        <div class="input-group-prepend">
                        <span class="input-group-text" id="basic-addon1"><i class="fa fa-filter"></i></span>
                        </div>
                        <input type="text" id="filter" name="filter" class="form-control" placeholder="Location Search" aria-label="Search" aria-describedby="basic-addon1">
                        </div>-->
                <div class="row m-t-40 searchable">


                    <div class="col-lg-6">
                        <h3>Western Locations</h3>







                        <div class="col-lg-12">
                            <h4><strong><a href="locations/ny_breast_imaging_north_massapequa.php"
                                        style="color:rgb(110,85,196);">NY Breast Imaging - North
                                        Massapequa</a></strong></h4>
                            <address>
                                1061 North Broadway, 1st Floor, North Massapequa, NY 11758<br>
                                <abbr title="Phone">P:</h4> <a href="tel:<?php echo $phone; ?>"><?php echo $phone; ?></a>
                            </address>
                        </div>



                        <div class="col-lg-12">
                            <h4><strong><a href="locations/ny-imaging-eastchester.php"
                                        style="color:rgb(110,85,196);"><?php echo $name; ?> - Eastchester</a></strong>
                            </h4>
                            <address>
                                2330 Eastchester Road, Bronx, NY 10469<br>
                                <abbr title="Phone">P:</h4> <a href="tel:<?php echo $phone; ?> "><?php echo $phone; ?> </a>
                            </address>
                        </div>



                        <div class="col-lg-12">
                            <h4><strong><a href="locations/ny_imaging_brooklyn.php"
                                        style="color:rgb(110,85,196);"><?php echo $name; ?> Brooklyn</a></strong></h4>
                            <address>
                                2236 Nostrand Ave, Brooklyn, NY 11210<br>
                                <abbr title="Phone">P:</h4> <a href="tel:<?php echo $phone; ?> "><?php echo $phone; ?> </a>
                            </address>
                        </div>



                        <div class="col-lg-12">
                            <h4><strong><a href="locations/ny_brooklyn_myrtle_avenue.php"
                                        style="color:rgb(110,85,196);"><?php echo $name; ?> Brooklyn - Myrtle
                                        Avenue</a></strong></h4>
                            <address>
                                172 Myrtle Ave, Brooklyn, NY 11201<br>
                                <abbr title="Phone">P:</h4> <a href="tel:<?php echo $phone; ?> "><?php echo $phone; ?> </a>
                            </address>
                        </div>



                        <div class="col-lg-12">
                            <h4><strong><a href="locations/forest-hills.php" style="color:rgb(110,85,196);">NY
                                        Imaging Forest Hills</a></strong></h4>
                            <address>
                                92-37 Metropolitan Avenue, Forest Hills, NY 11375<br>
                                <abbr title="Phone">P:</h4> <a href="tel:<?php echo $phone; ?> "><?php echo $phone; ?> </a>
                            </address>
                        </div>



                        <div class="col-lg-12">
                            <h4><strong><a href="locations/lake-success.php" style="color:rgb(110,85,196);">NY
                                        Imaging Lake Success</a></strong></h4>
                            <address>
                                1 Delaware Drive, Lake Success, NY 11042<br>
                                <abbr title="Phone">P:</h4> <a href="tel:<?php echo $phone; ?> "><?php echo $phone; ?> </a>
                            </address>
                        </div>



                        <div class="col-lg-12">
                            <h4><strong><a href="locations/ny_imaging_manhattan.php"
                                        style="color:rgb(110,85,196);"><?php echo $name; ?> Manhattan</a></strong></h4>
                            <address>
                                201 E 71st St, Lower Level, New York, NY 10021<br>
                                <abbr title="Phone">P:</h4> <a href="tel:646-597-5557">646-597-5557</a>
                            </address>
                        </div>

















                        <div class="col-lg-12">
                            <h4><strong><a href="locations/progressive_urology.php"
                                        style="color:rgb(110,85,196);">Progressive Urology</a></strong></h4>
                            <address>
                                27-47 Crescent St Suite 201, Queens, NY 11102<br>
                                <abbr title="Phone">P:</h4> <a href="tel:718-728-3200">718-728-3200</a>
                            </address>
                        </div>


                    </div>

                    <div class="col-lg-6">
                        <h3>Eastern Locations</h3>


                        <div class="col-lg-12">
                            <h4><strong><a href="locations/ny_imaging_westhampton.php"
                                        style="color:rgb(110,85,196);">East End Health and Imaging
                                        Center</a></strong></h4>
                            <address>
                                40 Main Street, Westhampton Beach, NY 11978<br>
                                <abbr title="Phone">P:</h4> <a href="tel:631-998-2500">631-998-2500</a>
                            </address>
                        </div>



















                        <div class="col-lg-12">
                            <h4><strong><a href="locations/patchogue.php" style="color:rgb(110,85,196);">NY
                                        Imaging Patchogue</a></strong></h4>
                            <address>
                                365 East Main Street, Patchogue, NY 11772<br>
                                <abbr title="Phone">P:</h4> <a href="tel:<?php echo $phone; ?> "><?php echo $phone; ?> </a>
                            </address>
                        </div>



                        <div class="col-lg-12">
                            <h4><strong><a href="locations/portjefferson.php" style="color:rgb(110,85,196);">NY
                                        Imaging Port Jefferson</a></strong></h4>
                            <address>
                                1500 Route 112 - Bldg 2A, Port Jefferson Station, NY 11776<br>
                                <abbr title="Phone">P:</h4> <a href="tel:<?php echo $phone; ?> "><?php echo $phone; ?> </a>
                            </address>
                        </div>



                        <div class="col-lg-12">
                            <h4><strong><a href="locations/ny_imaging_port_jefferson_building_1.php"
                                        style="color:rgb(110,85,196);"><?php echo $name; ?> Port Jefferson (Building
                                        1)</a></strong></h4>
                            <address>
                                1500 NY-112, Building 1, Port Jefferson Station, NY 11776<br>
                                <abbr title="Phone">P:</h4> <a href="tel:<?php echo $phone; ?>"><?php echo $phone; ?></a>
                            </address>
                        </div>



                        <div class="col-lg-12">
                            <h4><strong><a href="locations/ny_imaging_port_jefferson_station.php"
                                        style="color:rgb(110,85,196);"><?php echo $name; ?> Port Jefferson
                                        Station</a></strong></h4>
                            <address>
                                5316 Nesconset Highway, Port Jefferson Station, NY 11776<br>
                                <abbr title="Phone">P:</h4> <a href="tel:<?php echo $phone; ?> "><?php echo $phone; ?> </a>
                            </address>
                        </div>



                        <div class="col-lg-12">
                            <h4><strong><a href="locations/ny_imaging_specialists_riverhead.php"
                                        style="color:rgb(110,85,196);"><?php echo $name; ?> Riverhead</a></strong></h4>
                            <address>
                                750 Old Country Road, Riverhead, NY 11901<br>
                                <abbr title="Phone">P:</h4> <a href="tel:<?php echo $phone; ?> "><?php echo $phone; ?> </a>
                            </address>
                        </div>



                        <div class="col-lg-12">
                            <h4><strong><a href="locations/port-jefferson-station.php"
                                        style="color:rgb(110,85,196);"><?php echo $name; ?> Setauket</a></strong></h4>
                            <address>
                                49 Route 347, Port Jefferson Station, NY 11776<br>
                                <abbr title="Phone">P:</h4> <a href="tel:<?php echo $phone; ?> "><?php echo $phone; ?> </a>
                            </address>
                        </div>



                        <div class="col-lg-12">
                            <h4><strong><a href="locations/southampton.php" style="color:rgb(110,85,196);">NY
                                        Imaging Southampton</a></strong></h4>
                            <address>
                                640 County Road 39, Southampton, NY 11968<br>
                                <abbr title="Phone">P:</h4> <a href="tel:<?php echo $phone; ?> "><?php echo $phone; ?> </a>
                            </address>
                        </div>





                    </div>

                </div>
            </div>
        </div>
    </div>
</section>


<?php
include "footer.php"
?>