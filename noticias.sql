CREATE TABLE `_news_articles` (
 `id` int NOT NULL AUTO_INCREMENT,
 `title` varchar(255) NOT NULL,
 `link` varchar(255) NOT NULL,
 `pubDate` datetime NOT NULL,
 `author` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
 `description` text,
 `media_id` tinyint NOT NULL,
 `category_id` int DEFAULT NULL,
 `image` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
 `imageLink` varchar(255) DEFAULT NULL,
 `imageCaption` varchar(255) DEFAULT NULL,
 `content` text NOT NULL,
 `tags` varchar(255) DEFAULT NULL,
 `comments` int NOT NULL DEFAULT '0',
 `views` int NOT NULL DEFAULT '0',
 `inserted` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`id`),
 UNIQUE KEY `title` (`title`),
 FULLTEXT KEY `title_2` (`title`),
 FULLTEXT KEY `tags` (`tags`)
);

CREATE TABLE `_news_categories` (
 `id` int NOT NULL AUTO_INCREMENT,
 `media_id` int NOT NULL,
 `caption` varchar(40) NOT NULL,
 PRIMARY KEY (`id`)
);

CREATE TABLE `_news_comments` (
 `id` int NOT NULL AUTO_INCREMENT,
 `id_person` int NOT NULL,
 `id_article` int DEFAULT NULL,
 `content` varchar(255) NOT NULL,
 `inserted` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`id`)
);

CREATE TABLE `_news_media` (
 `id` int NOT NULL AUTO_INCREMENT,
 `name` varchar(20) NOT NULL,
 `caption` varchar(80) NOT NULL,
 `logo` varchar(255) NOT NULL,
 `description` varchar(255) NOT NULL,
 `type` enum('politics','economy','cultural','fashion','technology','science','other') NOT NULL,
 `filter` tinyint(1) NOT NULL DEFAULT '0',
 `inserted` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`id`),
 UNIQUE KEY `name` (`name`)
);

CREATE TABLE `_news_preferences` (
 `person_id` int NOT NULL,
 `selected_media` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
 PRIMARY KEY (`person_id`)
);

CREATE TABLE `_news_sources` (
 `id` int NOT NULL AUTO_INCREMENT,
 `media_id` int NOT NULL,
 `associated_category` int DEFAULT NULL,
 `name` varchar(255) NOT NULL,
 `source` varchar(255) NOT NULL,
 `scraper` varchar(255) NOT NULL,
 PRIMARY KEY (`id`)
);

INSERT INTO `_news_media` (`id`, `name`, `caption`, `logo`, `description`, `type`, `filter`, `inserted`) VALUES
(1, 'ddc', 'Diario de Cuba', 'ddc.png', '', 'politics', 1, '2020-07-04 05:57:12'),
(2, 'xataka', 'Xataka', 'xataka.png', 'Publicación de noticias sobre gadgets y tecnología', 'technology', 0, '2020-07-04 06:06:17'),
(3, 'andro4all', 'Andro4All', 'andro4all.png', 'Descubre todas las novedades sobre Android: últimas noticias, las mejores apps, nuevos móviles, trucos y mucho más', 'technology', 0, '2020-07-04 06:06:17'),
(4, 'tecnolike', 'TecnoLike Plus', 'tecnolike.png', 'Blog de tecnología en Cuba', 'technology', 0, '2020-07-04 06:06:17'),
(5, 'cubanet', 'Cubanet', 'cubanet.png', 'Noticias de la prensa independiente cubana desde 1994: Derechos Humanos, presos políticos y sociedad civil', 'politics', 0, '2020-07-17 14:59:28'),
(6, 'marti', 'Marti Noticias', 'marti.png', 'Radio Televisión Martí difunde informaciones originadas dentro de Cuba y reporta el acontecer noticioso mundial para todos los cubanos', 'politics', 0, '2020-07-17 16:03:36'),
(7, 'granma', 'Granma', 'granma.png', 'Periódico Granma, fundado el 3 de octubre de 1965. Su primera edición circuló el 4 de octubre.', 'politics', 0, '2020-07-17 17:15:50');
(8, 'cubadebate', 'Cuba Debate', 'cubadebate.jpg', 'Medio de información alternativa que alerta sobre campañas de difamación contra Cuba.', 'politics', 0, '2020-10-21 17:15:50'),
(9, 'digitalTrends', 'Digital Trends', 'digital_trends.jpg', 'Digital Trends en Español reporta las últimas novedades en tecnología con revisiones, videos, noticias y mucho más.', 'technology', '0', CURRENT_TIMESTAMP);

INSERT INTO `_news_categories` (`id`, `media_id`, `caption`) VALUES
(1, 1, 'Cuba'),
(2, 1, 'Internacional'),
(3, 1, 'Derechos Humanos'),
(4, 1, 'Cultura'),
(5, 1, 'Ocio'),
(6, 1, 'Deportes'),
(7, 1, 'De Leer'),
(8, '9', 'Android Army'),
(9, '9', 'Casa inteligente'),
(10, '9', 'Comunicados de prensa'),
(11, '9', 'Electrodomésticos'),
(12, '9', 'Features'),
(13, '9', 'Negocios'),
(14, '9', 'Opinión'),
(15, '9', 'Redes sociales'),
(16, '9', 'Telefonía celular'),
(17, '9', 'Apple'),
(18, '9', 'Cine en casa'),
(19, '9', 'Deportes'),
(20, '9', 'Entretenimiento'),
(21, '9', 'Fotografía'),
(22, '9', 'Noticias'),
(23, '9', 'Press Releases'),
(24, '9', 'Salud'),
(25, '9', 'Tendencias'),
(26, '9', 'Autos'),
(27, '9', 'Computación'),
(28, '9', 'Drones'),
(29, '9', 'Espacio'),
(30, '9', 'Guías'),
(31, '9', 'Ofertas'),
(32, '9', 'Realidad Virtual'),
(33, '9', 'Tecnología vestible'),
(34, '9', 'Videojuegos');

INSERT INTO `_news_sources` (`id`, `media_id`, `associated_category`, `name`, `source`, `scraper`) VALUES
(1, 1, 1, 'DDC Cuba', 'http://fetchrss.com/rss/5d7945108a93f8666f8b45675d7a44858a93f83a5e8b4569.xml', 'Ddc'),
(2, 1, 2, 'DDC Internacional', 'http://fetchrss.com/rss/5d7945108a93f8666f8b45675dbb9bc88a93f89b7e8b4567.xml', 'Ddc'),
(3, 1, 3, 'DDC Derechos Humanos', 'http://fetchrss.com/rss/5d7945108a93f8666f8b45675dbb9bdd8a93f8c0018b4567.xml', 'Ddc'),
(4, 1, 4, 'DDC Cultura', 'http://fetchrss.com/rss/5d7945108a93f8666f8b45675dbb9bf98a93f836018b4567.xml', 'Ddc'),
(5, 1, 5, 'DDC Ocio', 'http://fetchrss.com/rss/5d7945108a93f8666f8b45675dbb9c048a93f86a018b4567.xml', 'Ddc'),
(6, 1, 6, 'DDC Deportes', 'http://fetchrss.com/rss/5d7945108a93f8666f8b45675dbb9c1a8a93f8f7018b4568.xml', 'Ddc'),
(7, 1, 7, 'DDC De Leer', 'http://fetchrss.com/rss/5d7945108a93f8666f8b45675dbb9c288a93f8c0018b4568.xml', 'Ddc'),
(8, 2, NULL, 'Xataka Main', 'http://feeds.weblogssl.com/xataka2', 'Xataka'),
(9, 3, NULL, 'Andro4All Main', 'https://feeds.feedburner.com/andro4all', 'Andro4all'),
(10, 4, NULL, 'Tecnolike Main', 'http://fetchrss.com/rss/5d7945108a93f8666f8b45675ec8a8a48a93f8561a8b4567.xml', 'Tecnolike'),
(11, 5, NULL, 'Cubanet Main', 'http://fetchrss.com/rss/5d7945108a93f8666f8b45675e4f5cb88a93f86d3d8b4567.xml', 'Cubanet'),
(12, 6, NULL, 'Marti Main', 'http://www.martinoticias.com/api/epiqq', 'Marti'),
(13, 7, NULL, 'Granma Main', 'http://www.granma.cu/feed', 'Granma');
(14, 8, NULL, 'Cubadebate Main', 'http://www.cubadebate.cu/feed', 'Cubadebate'),
(NULL, '9', '8', 'DT Android Army', 'https://es.digitaltrends.com/android/feed/', 'DigitalTrends'),
(NULL, '9', '9', 'DT Casa inteligente', 'https://es.digitaltrends.com/inteligente/feed/', 'DigitalTrends'),
(NULL, '9', '10', 'DT Comunicados de prensa', 'https://es.digitaltrends.com/comunicados-de-prensa/feed/', 'DigitalTrends'),
(NULL, '9', '11', 'DT Electrodomésticos', 'https://es.digitaltrends.com/electrodomesticos/feed/', 'DigitalTrends'),
(NULL, '9', '12', 'DT Features', 'https://es.digitaltrends.com/features/feed/', 'DigitalTrends'),
(NULL, '9', '13', 'DT Negocios', 'https://es.digitaltrends.com/negocios/feed/', 'DigitalTrends'),
(NULL, '9', '14', 'DT Opinión', 'https://es.digitaltrends.com/opinion/feed/', 'DigitalTrends'),
(NULL, '9', '15', 'DT Redes sociales', 'https://es.digitaltrends.com/sociales/feed/', 'DigitalTrends'),
(NULL, '9', '16', 'DT Telefonía celular', 'https://es.digitaltrends.com/celular/feed/', 'DigitalTrends'),
(NULL, '9', '17', 'DT Apple', 'https://es.digitaltrends.com/apple/feed/', 'DigitalTrends'),
(NULL, '9', '18', 'DT Cine en casa', 'https://es.digitaltrends.com/cine/feed/', 'DigitalTrends'),
(NULL, '9', '19', 'DT Deportes', 'https://es.digitaltrends.com/deportes/feed/', 'DigitalTrends'),
(NULL, '9', '20', 'DT Entretenimiento', 'https://es.digitaltrends.com/entretenimiento/feed/', 'DigitalTrends'),
(NULL, '9', '21', 'DT Fotografía', 'https://es.digitaltrends.com/fotografia/feed/', 'DigitalTrends'),
(NULL, '9', '22', 'DT Noticias', 'https://es.digitaltrends.com/noticias/feed/', 'DigitalTrends'),
(NULL, '9', '23', 'DT Press Releases', 'https://es.digitaltrends.com/press-releases/feed/', 'DigitalTrends'),
(NULL, '9', '24', 'DT Salud', 'https://es.digitaltrends.com/salud/feed/', 'DigitalTrends'),
(NULL, '9', '25', 'DT Tendencias', 'https://es.digitaltrends.com/tendencias/feed/', 'DigitalTrends'),
(NULL, '9', '26', 'DT Autos', 'https://es.digitaltrends.com/autos/feed/', 'DigitalTrends'),
(NULL, '9', '27', 'DT Computación', 'https://es.digitaltrends.com/computadoras/feed/', 'DigitalTrends'),
(NULL, '9', '28', 'DT Drones', 'https://es.digitaltrends.com/drones/feed/', 'DigitalTrends'),
(NULL, '9', '29', 'DT Espacio', 'https://es.digitaltrends.com/espacio/feed/', 'DigitalTrends'),
(NULL, '9', '30', 'DT Guías', 'https://es.digitaltrends.com/guias/feed/', 'DigitalTrends'),
(NULL, '9', '31', 'DT Ofertas', 'https://es.digitaltrends.com/ofertas/feed/', 'DigitalTrends'),
(NULL, '9', '32', 'DT Realidad Virtual', 'https://es.digitaltrends.com/realidad-virtual/feed/', 'DigitalTrends'),
(NULL, '9', '33', 'DT Tecnología vestible', 'https://es.digitaltrends.com/vestibles/feed/', 'DigitalTrends'),
(NULL, '9', '34', 'DT Videojuegos', 'https://es.digitaltrends.com/videojuego/feed/', 'DigitalTrends');

