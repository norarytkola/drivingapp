<?php session_start();

    $palvelin= "localhost";
    $kayttaja = "root"; 
    $salasana = "";
    $tietokanta = "ajokirja";
    $yhteys = new mysqli($palvelin, $kayttaja, $salasana, $tietokanta);
    if ($yhteys->connect_error) {
        die("Yhteyden muodostaminen epäonnistui: " . $yhteys->connect_error);
    }

    if(isset($_POST["ok"])){
        if(isset($_POST["paiva"])&&($_POST["paiva"])!=""){
            $pvm=$_POST["paiva"];
        } else {
            $pvm=date("Y-m-d");
        };

        $ajaja=mysqli_real_escape_string($yhteys,$_POST['kuka']);
        $auto=mysqli_real_escape_string($yhteys,$_POST['auto']);
        $kilometrit=mysqli_real_escape_string($yhteys,$_POST['kilom']);
        $osoitelista="";
        $katu=$_POST["katu"];
        for($i=0; $i<count($katu); $i++){
            $osoitelista=$osoitelista."$katu[$i];";
        }
        if(!preg_match('/^[a-zA-Z0-9\s]+$/', $ajaja)){
            echo("Tarkista nimesi!");
        }else if(!isset($auto)){
            echo("Lisää auton rekisterinumero");
        }else {
            $insert="Insert into ajotaulu(ajaja, auto, pvm, kilometrit, osoitteet) values('$ajaja', '$auto', '$pvm' ,$kilometrit, '$osoitelista');" ;
            $lisatty=$yhteys->query($insert);
           
            
            }
        }
?>

<!DOCTYPE>
<html>
	<head>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <style>
            #paivansyotto{
                display:none;
            }
            .alert{
                position:absolute;
                top:4em;
                left:2em;
            }
            #lomakevastaus{
                font-size:2em;
                text-align:center;
                font-weight:bold;
            }       
            #map { 
                position:absolute;
                bottom:0; 
                height:100%; 
                width:100%;
                border:1px solid black
            }
         
        </style>

        <meta charset="UTF-8">	    
        <script src="//code.jquery.com/jquery-1.9.1.js"></script>
        <script>
                
            $(document).ready(function(event){ 
                $("#vaihdaPvm").click(function(){
                    $("#paivansyotto").css("display", "block");
                });
               $(window).resize(function(){
                    drawMap()
                });
                $(window).ready(function(){
                    drawMap()
                });           
            });               
        </script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
        <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
        <link href="https://api.tiles.mapbox.com/mapbox-gl-js/v1.6.0/mapbox-gl.css" rel="stylesheet" />
        <script src='https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-draw/v1.0.0/mapbox-gl-draw.js'></script>
        <meta name='viewport' content='initial-scale=1,maximum-scale=1,user-scalable=no' />
        <script src='https://api.tiles.mapbox.com/mapbox-gl-js/v1.3.2/mapbox-gl.js'></script>
        <script src="/javascriptfile.js" type="text/javascript"></script>         
</head>      


<body>

    <nav class="navbar navbar-dark bg-dark">
        <h1 class="nav-item text-white ml-5">AJOVAHTI </h1>
        <ul class="nav nav-tabs">
            <a class="nav-link disabled" href="#">Lisää ajo</a>
            <a class="nav-link" href="/ajohistoria.php" style="color:white">Ajohistoria</a>
        </ul>
    </nav>

    <div class="container-fluid">

        <div id="lomakevastaus"> 
            <?php if (isset($lisatty) && $lisatty==false){ 
                echo "Lisäys ei valitettavasti onnistunut ei onnistunut. Yritä uudelleen ja tarkista, että antamasi tiedot ovat oikein"; 
            } else if(isset($lisatty) && $lisatty==true){
                echo "Lisäys onnistui!";
                } 
            ?>
        </div>
    <div class="row p-5"></div>

    <div class="row justify-content-center ml-3" id="divi">

        <div class="col-xl-5 col-lg-6 col-sm-10 col-md-10 pt-5 mr-5">
            <form action="ajopvk.php"  method="post" id="lomake" >
                <p id="muistutus"></p>

                <div id="osoitelista"  >
                    <h4 class="mb-3">Mistä?</h4>  

                    <div id="osoitekentta" class="form-group">
                        <input type="text" class="katu form-control" name="katu[]" placeholder="katu, talon numero, paikkakunta" required onKeyUp="autoComplete(this)">
                    </div>
            
                    <div class="form-group">
                        <button id="nappi" onClick="newAddress()" class="btn btn-secondary">Lisää välietappi</button>
                    </div> 

                    <p id="lisakentta"></p>

                    <h4 class="mb-3">Minne?</h4>  

                    <div class="form-group">
                        <input type="text" class="katu form-control" name="katu[]"  placeholder="katu, talon numero, paikkakunta" required onKeyUp="autoComplete(this)">
                    </div> 
                </div>

                <h4 class="mb-3">Kuka?<h4>

                <div class="form-group" onChange="countDistance()" >
                    <input type="text"  class="form-control" id="kuka" name="kuka" placeholder="ajaja" required>
                </div>

                <h4 class="mb-3">Auto:<h4>

                <div class="form-group mb-5">
                    <input type="text" class="form-control" name="auto" placeholder="rekisterinumero" id="rekkari" required>
                </div> 

                <div  class="form-control mb-3">
                    Ei tänään: <input type="checkbox" id="vaihdaPvm">
                </div>
                <div  id="paivansyotto">
                    Syötä päivämäärä:
                    <div class="form-group">
                        <input type="text" class="form-control" name="paiva" id="paiva" placeholder="vvvv-kk-pp">
                    </div>
                </div>

                <div  class="form-group">
                    <p id="kilsat"></p>
                    <input type="hidden" name="kilom" id="kilom" required>
                </div>
                <div class=" form-group">
                    <input type="submit" name="ok" value="Lisää" class="btn btn-secondary">
                </div>
            </form>
        </div>
        <div class="col-xl-5 col-lg-6 col-sm-10 col-md-10 mt-5" id="kehys" style="position:relative;">
            
            <div id="map"></div>
        </div> 
    </div>
</div>
  
          
</body>
</html>