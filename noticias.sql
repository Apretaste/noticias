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
(8, 'cubadebate', 'Cuba Debate', 'cubadebate.jpg', 'Medio de información alternativa que alerta sobre campañas de difamación contra Cuba.', 'politics', 0, '2020-10-21 17:15:50');

INSERT INTO `_news_categories` (`id`, `media_id`, `caption`) VALUES
(1, 1, 'Cuba'),
(2, 1, 'Internacional'),
(3, 1, 'Derechos Humanos'),
(4, 1, 'Cultura'),
(5, 1, 'Ocio'),
(6, 1, 'Deportes'),
(7, 1, 'De Leer');

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
(14, 8, NULL, 'Cubadebate Main', 'http://www.cubadebate.cu/feed', 'Cubadebate');