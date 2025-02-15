<?php
//Este php es para probar la capacidad de simplepie al
//momento de parsear varios feeds

require_once('simplepie-1.5/autoloader.php');

$feeds = new SimplePie();

$urls = ['https://www.xataka.com/feedburner.xml',
    'https://feeds.bbci.co.uk/news/world/rss.xml',
    'https://www.cnbc.com/id/100727362/device/rss/rss.html',
    'https://abcnews.go.com/abcnews/internationalheadlines'];

$feeds->set_feed_url($urls); //Se le dan los links de los feeds a simplepie
$feeds->enable_cache(false); //Desactivarlo para evitar una advertencia tonta
$feeds->init(); //Con este comando comienza el proceso de obtener y parsear los feeds
$feeds->handle_content_type(); //Para que el contenido mostrado se muestre bien en html
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test 2</title>
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
    <?php foreach ($feeds->multifeed_objects as $feed): ?>

        <div class="header">
		    <h1><a href="<?php echo $feed->get_permalink(); ?>"><?php echo $feed->get_title(); ?></a></h1>
		    <p><?php echo $feed->get_description(); ?></p>
	    </div>

        <?php for ($i = 1; $i <= 4; $i++): ?>
            <?php $item = $feed->get_item($i) ?>
            <div class="item">
			    <h2><a href="<?php echo $item->get_permalink(); ?>"><?php echo $item->get_title(); ?></a></h2>
			    <p><?php echo $item->get_description(); ?></p>
			    <p><small>Posted on <?php echo $item->get_date('j F Y | g:i a'); ?></small></p>
		    </div>
        <?php endfor; ?>

    <?php endforeach; ?>
</body>
</html>