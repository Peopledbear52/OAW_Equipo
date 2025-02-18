<?php
//Este php es para probar todas las funciones de simplepie

require_once('simplepie-1.5/autoloader.php');

$feed = new SimplePie();

$feed->set_feed_url('https://www.cnbc.com/id/100727362/device/rss/rss.html'); //Se le dan los links de los feeds a simplepie
$feed->enable_cache(false); //Desactivarlo para evitar una advertencia tonta
$feed->init(); //Con este comando comienza el proceso de obtener y parsear los feeds
$feed->handle_content_type(); //Para que el contenido mostrado se muestre bien en html

$item = $feed->get_item(1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test 3</title>
    <style type="text/css">
	body {
		font:12px/1.4em Verdana, sans-serif;
		color:#333;
		background-color:#fff;
		width:700px;
		margin:50px auto;
		padding:0;
	}
 
	a {
		color:#326EA1;
		text-decoration:underline;
		padding:0 1px;
	}
 
	a:hover {
		background-color:#333;
		color:#fff;
		text-decoration:none;
	}
 
	div.header {
		border-bottom:1px solid #999;
	}
 
	div.item {
		padding:5px 0;
		border-bottom:1px solid #999;
	}
	</style>
</head>
<body>
    <div class="header">
        <h1><?php echo $feed->get_permalink(); ?></h1>
        <h1><?php echo $feed->get_title(); ?></h1>
        <h1><?php echo $feed->get_description(); ?></h1>
        <img src= <?php echo $feed->get_image_url(); ?>>
	</div>

    <div class="item">
        <h1><?php echo $item->get_permalink(); ?></h1>
        <h1><?php echo $item->get_title(); ?></h1>
        <h1><?php echo $item->get_description(); ?></h1>
        <? //Loop para obtener todas las categorias, si no hay, get_categories dara null ?>
        <?php foreach ($item->get_categories() as $category): ?>
            <h1>Categoria: <?php echo $category->get_label(); ?></h1>
        <?php endforeach; ?>
        <h1><?php echo $item->get_date('j F Y | g:i a'); ?></h1>
        <? //Loop para obtener todos los autores, si no hay autores, get authors dara null ?>
        <?php foreach ($item->get_authors() as $author): ?>
            <h1>Autor: <?php echo $author->get_name(); ?></h1>
        <?php endforeach; ?>
	</div>
</body>
</html>