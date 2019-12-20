<?php
    //session avaaminen
    session_start();
    $server= "localhost";
    $user = "root"; 
    $password = "";
    $dataTable= "ajokirja";
    $connection = new mysqli($server, $user, $password, $tietokanta);
    if ($connection->connect_error) {
        die("Yhteyden muodostaminen epäonnistui: " . $connection->connect_error);
    }
?>

<!DOCTYPE>
<html>
	<head>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <style>
            .alert{
                position:absolute;
                top:4em;
                left:2em;
            }
            @media-screen and (max-width:700px){
                #chart{
                    display:none;
                }
            }
            #info{
                font-size:2em;
                text-align:center;
                font-weight:bold;
            }
            #naytaUusimmat{
                display:none;
            }  
        </style>

		<meta charset="UTF-8">	
        <script src="/drivingapp/javascriptfile.js" type="text/javascript"></script>
        <script src="//code.jquery.com/jquery-1.9.1.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
        <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
        <script>
            $(document).ready(function(){
                google.charts.load('current', {'packages':['corechart']});
                google.charts.setOnLoadCallback(drawChart);
                $(window).resize(function(){
                    drawChart();
                })
            })
        </script>
    </head>

<body>
    <nav class="navbar navbar-dark bg-dark">
        <h1 class="nav-item text-white ml-5">AJOHISTORIA </h1>
        <ul class="nav nav-tabs">
            <a class="nav-link text-white" href="/drivingapp/ajopvk.php">Lisää ajo</a>
            <a class="nav-link disabled" href="/drivingapp/ajohistoria.php" style="color:white">Ajohistoria</a>
        </ul>
    </nav>

    <div id="info"  class="pt-5">
        <?php if(isset($onnistunutPoisto)){
                 if($onnistunutPoisto==true){
                    echo "Poisto onnistui";
                } else if($onnistunutPoisto==false){
                    echo "Poisto ei jostain syystä onnistunut. Yritä myöhemmin uudelleen";
                } 
            }
        ?>
    </div>

    <div class="container-fluid">  
        <div class="row justify-content-center pt-5">
            <div class="col-xl-8 col-lg-8 col-md-10 col-sm-12 col-xs-12">
            <form action="ajohistoria.php" method="post" onChange="setValue()">
                <div id="haku">
                    <div  class="form-row">
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text" >
                                Hae kuukausi:</span>
                            </div> <input type="number" name="kuukausihaku" placeholder="kk" class="form-control" ><input type="number"  class="form-control" name="vuosihaku" placeholder="vvvv">
                        </div>
                    </div>
                    <div class="input-group mb-5">
                        <div class="input-group-prepend">
                            <span class="input-group-text" >
                                Hae auton rekisterinumerolla:   
                            </span>
                        </div>
                        <select id='selection' name='select' onChange="getSelected()" class="form-control"><option></option>             
                            <?php 
                    
                                $onnistunuthaku=$connection->query("select distinct auto from ajotaulu");
                                while($rnro=$onnistunuthaku->fetch_assoc()){
                                    echo("<option value='".$rnro['auto']."'>".$rnro['auto']."</option>");
                                    }
                            ?>
                        </select>
                        <div class="input-group-append">     
                            <input type="submit" name="filter" value="Hae" class="btn btn-secondary" id="jQ">  
                            <button id="naytaUusimmat" onClick="piilota()" class="btn btn-secondary">Näytä 10 uusinta</button>
                        </div>
                    </div>
                </div>               
                 
                <table class="table text-center">
                    <thead class="thead-light">
                        <tr > 
                        <th scope="col">Päivä:</th>
                        <th scope="col">Kilometrit:</th>
                        <th scope="col">Ajaja:</th>
                        <th scope="col">Auto:</th>
                        <th scope="col">Osoitteet:</th>
                        <th scope="col"><input type="submit" name="poisto" value="Poista valitut" class="btn btn-light btn-block" > </th>
                        </tr>
                    </thead>
                    <tbody>

        <?php 
            if(isset($_POST["filter"])){
                $hakuvv=mysqli_real_escape_string($connection,$_POST["vuosihaku"]);
                $hakukk=mysqli_real_escape_string($connection,$_POST["kuukausihaku"]);
                $hakurekkari=mysqli_real_escape_string($connection,$_POST["select"]);
                $hakusql="select * from ajotaulu ";
                if(isset($hakurekkari)&&$hakurekkari!=""&&isset($hakukk)&&($hakukk)!=""&&isset($hakuvv)&&$hakuvv=""){ 
                    $hakusql.=" where auto='$hakurekkari' and pvm='$hakuvv-$hakukk-%%";   
                } else if(isset($hakurekkari)&&$hakurekkari!=""){
            
                    $hakusql.="where auto='$hakurekkari'";
                    $tulokset=$connection->query($hakusql);
                } else if(isset($_POST["vuosihaku"])&&($_POST["vuosihaku"]!="")&&(isset($_POST["kuukausihaku"])&&($_POST["kuukausihaku"]!=""))){
                    $hakusql.="where pvm between '$hakuvv-$hakukk-01' and '$hakuvv-$hakukk-31'";
                }
                    $tulokset=$connection->query($hakusql);
                    if($tulokset==true){
                        while($rivi=$tulokset->fetch_assoc()){
                            $lista=explode(";",$rivi["osoitteet"]);
                            $pudotusvalikko="<ul  class='list-group-flush'>";
                            foreach($lista as $i=>$singleone){
                                $pudotusvalikko.="<li class='list-group-item list-group-item-secondary'>$singleone</li>";
                            }
                            $pudotusvalikko.="</ul>";
                            echo "<tr class='table-secondary'><td>".$rivi['pvm']. " </td><td> " .$rivi['kilometrit']. "</td><td>" .$rivi['ajaja']. " </td><td>" .$rivi['auto']. "</td><td>$pudotusvalikko</td><td>  <input type='checkbox' style='margin-right:0.5em;' class='poistettavat' name='poistettavat[]' id='".$rivi['ajo_id']."'>  valitse</td></tr>";        
                        }
                    } else {
                        echo("Hakusi ei tuottanut yhtään tuloksia.");
                    }
        ?>

        <SCRIPT>
                const nappi=document.getElementById("naytaUusimmat");
                nappi.style.display="block";
        </SCRIPT>
        <?PHP
            }else {
                $sql="select * from ajotaulu ORDER BY pvm DESC limit 10";
                $tulokset=$connection->query($sql);
                while($rivi = $tulokset->fetch_assoc()) {
                    $lista=explode(";",$rivi["osoitteet"]);
                    $pudotusvalikko="<ul  class='list-group-flush'>";
                    foreach($lista as $i=>$singleone){
                        $pudotusvalikko.="<li class='list-group-item  list-group-item-secondary'>$singleone</li>";
                    }
                    $pudotusvalikko.="</ul>";
                    $formattedNum = number_format($rivi["kilometrit"], 2);
                    echo "<tr class='table-secondary'><td>".$rivi['pvm']. " </td><td> " .$formattedNum. "</td><td>" .$rivi['ajaja']. " </td><td>" .$rivi['auto']. "</td><td>$pudotusvalikko</td><td class='text-danger'>  <input type='checkbox'  class='poistettavat' name='poistettavat[]' id='".$rivi['ajo_id']."'>valitse</td></tr>";      
                }
            }

            if(isset($_POST["poisto"])){
                foreach($_POST["poistettavat"] as $valinta){
                    if (is_numeric($valinta)){  
                            $sqlPoisto="delete from ajotaulu where ajo_id='$valinta'";
                            $onnistunutPoisto=$connection->query($sqlPoisto); 
                    
                    }
                }   
            }                                    
        ?>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>

        <div class="row justify-content-center pt-5">
            <div class="col-xl-8 col-lg-8 col-md-10 col-sm-12 col-xs-12">
                <div id="kehys">
                    <div id="chart" style="opacity:0.8; width:100%; height:30em; object-fit: fill;"></div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
