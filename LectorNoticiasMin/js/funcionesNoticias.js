actualizarNoticias();

// Añadir evento para el cambio de filtro
const radios = document.querySelectorAll('input[name="filtro"]');
radios.forEach(radio => {
    radio.addEventListener('change', function () {
        // Obtener el valor del filtro seleccionado
        const filtro = document.querySelector('input[name="filtro"]:checked').value;
        // Llamar a la función que recargará las noticias con el filtro seleccionado
        cargarNoticias(filtro);
    });
});


async function cargarNoticias(filtro) {
    try {
        //Se conecta al php getnoticias_ordenado.php
        const url = `php/getnoticias_ordenado.php?order=${filtro}`;

        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        const data = await response.json();

        //Si hay algun error, este if lo identificará y lanzará un error
        if (data.mensaje) {
            throw new Error(data.mensaje);
        }

        //Los datos obtenidos serán procesados por la función mostrarElementos
        mostrarElementos(data);
    } catch (error) {
        console.error("Error: ", error);
        alert("Hubo un error al obtener las noticias.");
    }
}

//Esta función permite leer las feeds para ingresar las noticias en el lector
async function leerNoticiasRSS() {
    try {
        if (!esURLValida(document.getElementById("feeds").value.toString())) {
            alert("No es una URL válida");
            return false;
        }
        await insertarFeed();
        await insertarSecuencia();
        const filtro = document.querySelector('input[name="filtro"]:checked').value;
        alert(filtro);
        await cargarNoticias(filtro);

    } catch (error) {
        console.error("Error:", error);
        alert("Hubo un error al leer el feed.");
    }
}

//Esta función permite actualizar la página
async function actualizarNoticias() {
    try {
        await insertarSecuencia();
        const filtro = document.querySelector('input[name="filtro"]:checked').value;
        alert(filtro);
        await cargarNoticias(filtro);

    } catch (error) {
        console.error("Error:", error);
        alert("Hubo un error al leer el feed.");
    }
}


//El evento en el que la barra de búsqueda es oprimido
document.getElementById("buscador").addEventListener("keyup", function (event) {
    if (event.key === "Enter") { // Si la tecla presionada es "Enter"
        buscarNoticias();
    }
});

//Esta función permite encontrar noticias de algo en particular
async function buscarNoticias() {//modificar esto
    try {
        //Se conecta al php buscar.php
        const info = document.getElementById("buscador").value.toString();
        const filtro = document.querySelector('input[name="filtro"]:checked').value;
        alert(filtro);

        const url = "php/buscar_ordenado.php?q=" + info + "&order=" + filtro;

        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        const data = await response.json();

        //Si hay algun error, este if lo identificará y lanzará un error
        if (data.mensaje) {
            throw new Error(data.mensaje);
        }

        //Los datos obtenidos serán procesados por la función mostrarElementos
        mostrarElementos(data);
    } catch (error) {
        console.error("Error: ", error);
        alert("Hubo un error al obtener las noticias.");
    }
}

//---1. insertamos el feed---
//Esta función se conecta al php insertfeed.php
//Recibe un JSON del php y lo procesa
async function insertarFeed() {
    try {
        /*Url del feed por añadir, SOLO acepta UN url por llamada
        Podria modificarse el php para que acepte un array de urls
        y añadir todos los feeds, chance luego lo hago*/
        const feedUrl = {
            url: document.getElementById("feeds").value.toString()
        };

        const url = "php/insertfeed.php"; //Url del php al que se llamara

        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(feedUrl) //Aqui se manda el JSON que queremos que reciba el php
        });

        if (!response.ok) {
            //Si no se puede establecer conexión con el php, se mandara un error
            throw new Error(`Error HTTP: ${response.status}`);
        }

        const data = await response.json();//Se espera una respuesta del php, y se convierte en un json

        //El php mandará un json con la llave mensaje
        alert(data.mensaje);

    } catch (error) {
        console.error("Error: ", error);
        alert("Hubo un error al insertar el feed.");
    }
}

//---2. insertamos las noticias en la bd---
//Esta función ejecuta las otras dos en secuencia
async function insertarSecuencia() {
    try {
        const feeds = await obtenerFeeds();
        await insertarNoticias(feeds);
    } catch (error) {
        console.error("Error:", error);
    }
}

//Esta función obtiene los feeds que estan en la db
//Esto es necesario ya que luego se insertarán las noticias de los feeds obtenidos aqui
async function obtenerFeeds() {
    try {
        const url = "php/getfeeds.php";

        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        const data = await response.json();
        //Se obtiene un objeto json que contiene la info de todos los feeds
        return data;
    } catch (error) {
        console.error("Error: ", error);
        alert("Hubo un error al obtener los feeds.");
    }
}

//Esta función llama al php que insertara las noticias de los feeds obtenidos
async function insertarNoticias(feeds) {
    try {
        const feedsUrl = {};

        /* Aqui el JSON feeds sera escaneado y su información será asignada al json feedsUrl
        La información que se pone en feedsUrl es información que necesitará insertnoticias.php para funcionar*/
        feeds.forEach(feed => {
            feedsUrl[feed.titulo] = {
                url: feed.url, //El url es necesario ya que funciona como llave foranea para las noticias
                rssurl: feed.rssurl //Este url es necesario para que simplepie escanee las noticias del feed
            }
        });

        const url = "php/insertnoticias.php";

        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(feedsUrl)
        });

        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        const data = await response.json();

        alert(data.mensaje);
    } catch (error) {
        console.error("Error: ", error);
        alert("Hubo un error al insertar las noticias.");
    }
}

//---3.Se presentan las noticias---
//Está función obtiene todas las noticias obtenidas en la db
async function obtenerNoticias() {
    try {
        //Se conecta al php getnoticias.php
        const url = "php/getnoticias.php";

        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        const data = await response.json();

        //Si hay algun error, este if lo identificará y lanzará un error
        if (data.mensaje) {
            throw new Error(data.mensaje);
        }

        //Los datos obtenidos serán procesados por la función mostrarElementos
        mostrarElementos(data);
    } catch (error) {
        console.error("Error: ", error);
        alert("Hubo un error al obtener las noticias.");
    }
}

function mostrarElementos(data) {
    const contenedorDiv = document.getElementById("noticias-container");
    contenedorDiv.innerHTML = ""; //limpiamos el contenedor de todas las noticias

    /*Aqui se recorrerá cada elemento del json data, cada elemento es una noticia
    Su información será asignada a un Div y mostrada por el div "texto"
    De nuevo, esto es solo para mostrarles como es que uno puede obtener la información de cada noticia
    Como utilizar esta información será ya su jurisdicción */
    data.forEach(noticia => {

        const categoria = noticia.categorias;

        /* Dentro de este IF se crea un array de categorias cuando se detecta 
        que hay mas de una, ahora mismo lo comento porque no me sirve, pero si necesitan
        obtener un array con cada categoria incluida, pueden usarlo.
        if (categoria.includes("|")) {
            const catArray = categoria.split("|");
        }*/

        const cartaDiv = document.createElement("div");

        const enlaceDiv = document.createElement("a");
        enlaceDiv.className = "link-noticia";
        enlaceDiv.href = `${noticia.urlnoticia}`;

        const noticiaDiv = document.createElement("div");
        noticiaDiv.className = "card";

        const imagenDiv = document.createElement("div");//esta no tendra imagen en esta entrega pero este div al final debería tener una etiqueta de <img>
        imagenDiv.className = "card-image";
        imagenDiv.innerHTML = `<img src="${noticia.urlimagen}" alt="Imagen" width="400px" height="100px">`;


        const contenidoDiv = document.createElement("div");
        contenidoDiv.className = "card-content";

        contenidoDiv.innerHTML = `<div class="card-title">${noticia.titulo}</div><div class="card-date">${noticia.fecha}</div><div class="card-category">Categoria:${categoria}</div><div class="card-text">${noticia.descripcion}</div>`

        noticiaDiv.appendChild(imagenDiv);
        noticiaDiv.appendChild(contenidoDiv);

        enlaceDiv.appendChild(noticiaDiv);

        cartaDiv.appendChild(enlaceDiv);

        contenedorDiv.innerHTML += cartaDiv.innerHTML;
    });
}

function esURLValida(texto) {
    try {
        new URL(texto)
        return true;
    } catch (error) {
        return false;
    }
}