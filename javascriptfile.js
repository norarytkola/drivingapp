let map;
let draw;
let navigationcoordinates=[];


function drawMap(){
    let centerC=[24.928794, 60.165759];
    mapboxgl.accessToken ='pk.eyJ1Ijoibm9yYXJ5dGtvbGEiLCJhIjoiY2swZGg4cHV1MDdmbDNkcno5eHVnZXJkaiJ9.SovgLZ_LrV-jhUPNeZuETg';
        map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/streets-v11',
            center: centerC,
            zoom: 9
        });
    }



function haeNavireitti(navihaku){

    fetch(`https://api.mapbox.com/directions/v5/mapbox/driving/${navihaku}?steps=true&access_token=pk.eyJ1Ijoibm9yYXJ5dGtvbGEiLCJhIjoiY2swZGg4cHV1MDdmbDNkcno5eHVnZXJkaiJ9.SovgLZ_LrV-jhUPNeZuETg`)
    .then(response=>{
        return response.json() })
    .then(res=>{
        navigationcoordinates=[];
        const coords=res.routes[0].legs[0].steps;
        for(let i=0; i<coords.length; i++){
            let kasiteltavasteppi=coords[i];
            for(let a=0; a<kasiteltavasteppi.intersections.length; a++){    
                let listaan=kasiteltavasteppi.intersections[a].location
                navigationcoordinates.push(listaan)
            }
        } centerC=navigationcoordinates[0];
        const kartanPisteet=JSON.stringify(navigationcoordinates)
        drawLine(kartanPisteet);
    })
}

function drawLine(kartanPisteet){

    if (map.getLayer("route")) {
        map.removeLayer("route");
    }

      map.addLayer({
            "id": "route",
            "type": "line",
            "source": {
                "type": "geojson",
                "data": {
                    "type": "Feature",
                    "properties": {},
                    "geometry": {
                        "type": "LineString",
                        "coordinates":JSON.parse(kartanPisteet),

                    },
                },
            },
            "layout": {
                "line-join": "round",
                "line-cap": "round"
            },
            "paint": {
                "line-color": "black",
                "line-width": 3
            }
        })




 
}

function drawChart(){

    function getJson(){                                               //luetaan data ulkoisesta tiedostosta..
        return new Promise(resolve=>{
            const xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange=function (){
                if (this.readyState == 4) {
                const dom=JSON.parse(this.responseText);
                resolve(dom);
                } 
            }
            xhttp.open("GET", "/tiedot.php", true);
            xhttp.send()
        })
    }  

    function getData(dom){                                           //haetaan data kuukausittaisiakilometrimääria varten..
        return new Promise(resolve=>{
            for(let i=dom.length; dom.length<=11; i++){
                dom.push({kk:dom.length+1, km:0})
            }                                                        //jaetaan palkkeihin kuukausittain..
            const data=google.visualization.arrayToDataTable([
                ['Kuukausi', 'Kilometrejä', { role: 'style' } ],
                ['Tammikuu', dom[0].km, 'color:#0066ff'],
                ['Helmikuu', dom[1].km, 'color:#993399'],
                ['Maaliskuu', dom[2].km,'color:#99ff33'],
                ['Huhtikuu', dom[3].km,  'color:#cc0066'],
                ['Toukokuu', dom[4].km, 'color:#ff9933'],
                ['Kesäkuu', dom[5].km,  'color:#cc00ff'],
                ['Heinäkuu',  dom[6].km,  'color:#336600'],
                ['Elokuu',  dom[7].km, 'color:#33ffff'],
                ['Syyskuu',  dom[8].km,  'color:#ff0000'],
                ['Lokakuu',  dom[9].km,  'color:#660066'],
                ['Marraskuu',  dom[10].km, 'color:#009966'],
                ['Joulukuu',  dom[11].km, 'color:#ffff33']
                ]);
            resolve(data);
        })
    }
    var options = {
            title: "Kuluvan vuoden ajot",
            legend: { position: 'top:50', maxLines: 12 },
            bar: { groupWidth: '75%' },
            y:100,
    }

    var chart = new google.visualization.ColumnChart(document.getElementById("chart"));

    return getJson()
        .then(dom=>{
    return getData(dom) })
        .then(data=>{
            chart.draw(data, options) })      
}


function newAddress(){                                              //lisäetappia varten kopioidaan osoitekentät ja lisätään kopio sivulle
    event.preventDefault();
        const toCopy=document.getElementById("osoitekentta");
        const clone=toCopy.cloneNode(true);
        document.getElementById("lisakentta").appendChild(clone);
 }




function countDistance(){
  
    let navihaku;
    let getAddresses=new Promise(resolve=>{                         //haetaan osoitelista..
        const addressList=[]
        const katu=document.getElementsByClassName("katu");
        var re = /^[A-Za-z]+$/;
        for (let z=0; z<katu.length; z++){
                const val=katu[z].value;
                if(val!=""){
                addressList.push(val);
                }else{return}
        }
    
        resolve(addressList)
    })

        let getUrl=function(addressList){                           //muodostetaan osoitelistasta url-lista jolla haetaan koordinaatit apista..
                return new Promise(resolve=>{
                    const urlList=[];
                    addressList.forEach(function(address){
                    const url=`https://api.mapbox.com/geocoding/v5/mapbox.places/${address}.json?country=FI&access_token=pk.eyJ1Ijoibm9yYXJ5dGtvbGEiLCJhIjoiY2swZGg4cHV1MDdmbDNkcno5eHVnZXJkaiJ9.SovgLZ_LrV-jhUPNeZuETg`;
                    urlList.push(url);
                    })
                resolve(urlList)
                })
    
        };
        getCoordinates=(urlList)=>{                                 //haetaan koordinaatit yksitellen apista ja palautetaan lista..
            return new Promise(resolve=>{
                let coordinates=[]
                const allRequests=urlList.map(url=>
                    fetch(url).then(function(response){
                        return response.json()
                        })
                    .then(function(featureCollection){
                            const coord=featureCollection.features[0].geometry.coordinates;
                            const lon=coord[0];
                            const lat=coord[1];
                            coordinates.push({lon, lat})
                            })    
                )
                
                Promise.all(allRequests)
                .then(res=>{
                    resolve(coordinates)
                })
                
            })
        }

        function hakusana(cList) {                                  //muodostetaan koordinaattilistasta hakusana uutta api-kyselyä varten..
            let haku="";
                for (let i=0; i<cList.length; i++){
                    haku+=`${cList[i].lon},${cList[i].lat}`;
                        if(i!=cList.length-1){
                            haku+=";";
                        }
            }
            return haku;    
        };

        kilometrit=(haku)=> {                                       //haetaan apista kokonaismatka
            return new Promise(resolve=>{
                  fetch(`https://api.mapbox.com/directions/v5/mapbox/driving/${haku}?&access_token=pk.eyJ1Ijoibm9yYXJ5dGtvbGEiLCJhIjoiY2swZGg4cHV1MDdmbDNkcno5eHVnZXJkaiJ9.SovgLZ_LrV-jhUPNeZuETg`) 
                        .then(function(response){
                            return response.json() 
                            })
                        .then(function(object){
                            const dist=object.routes[0].distance;
                            const distance=dist/1000;   
                            resolve(distance)
                        })
                    
            })
        }

        getAddresses.then(addressList=>{
            return getUrl(addressList)  }) 
                .then(urlList=>{
                    return getCoordinates(urlList)  })
                .then(cList=>{
                    return hakusana(cList)  })
                .then(haku=>{
                    navihaku=haku;
                    return kilometrit(haku)  })
                .then(distance=>{
                   document.getElementById("kilom").value=distance;
                   const divi=document.getElementById("kilsat");
                   divi.innerHTML=distance+"km";
                   haeNavireitti(navihaku)  
                        
                       
            })  
                
        }


function setValue(){                                                //määritetään checkbox-valuet php-poistoa varten. Php suorittaa näiden avulla tietokannasta poiston käyttäjän niin määrätessä
    const checkbox=document.getElementsByClassName("poistettavat");
    for (let i=0; i<checkbox.length; i++){
        if (checkbox[i].checked == true ){
            checkbox[i].value=checkbox[i].id;
            console.log(checkbox[i].value)
        }
    }
}

function piilota(){                                                 //elementti on näkyvä vain jos ajohistoria-lista on suodatettu käyttäjän hakusanalla. Kun elementtiä painaa, suodatus nollaantuu eikä napille tarvetta näkyä.
    const element=document.getElementById("naytaUuusimmat");
    element.style.display="none";
}





function autoComplete(element){                                     //haetaan apista ehdotuksia käyttäjän hakusanan mukaan ja luodaan option-valikko input-tagille
    const parent=element.parentNode; 
    const random=Math.random().toString(36).substr(2, 9);
    const datalist=document.createElement("datalist");
    datalist.setAttribute("id", random);
    const re=/^[0-9]+$/;
    fetch(`https://api.mapbox.com/geocoding/v5/mapbox.places/${element.value}.json?country=FI&access_token=pk.eyJ1Ijoibm9yYXJ5dGtvbGEiLCJhIjoiY2swZGg4cHV1MDdmbDNkcno5eHVnZXJkaiJ9.SovgLZ_LrV-jhUPNeZuETg`)
    .then(result=>{
        return result.json()
    .then(function(collection){
        let lista=collection.features;
        for(let i=0; i<lista.length; i++){
            let ehdotus=lista[i].place_name;
            let ehdotuslista=ehdotus.split(",");
            ehdotuslista.pop();
            ehdotuslista.pop();
            ehdotus=ehdotuslista.toString();
            var node = document.createElement("option"); 
            node.setAttribute("id", random);
            node.setAttribute("onClick", "valitse(this)")
            node.innerHTML=ehdotus;
            datalist.appendChild(node); 
        }
    parent.appendChild(datalist);
    element.setAttribute("list", random)
       
    })
    })
 }

 

