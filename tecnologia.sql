CREATE TABLE `_tecnologia_articles` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL,
  `pubDate` datetime NOT NULL,
  `author` varchar(40),
  `description` text,
  `source_id` tinyint(4) NOT NULL,
  `image` varchar(40),
  `imageLink` varchar(255),
  `imageCaption` varchar(255),
  `content` text NOT NULL,
  `comments` int(11) NOT NULL DEFAULT '0'
);

CREATE TABLE `_tecnologia_sources` (
  `id` int(11) NOT NULL,
  `name` varchar(20) NOT NULL,
  `url` varchar(100) NOT NULL
);

INSERT INTO `_tecnologia_sources` (`id`, `name`, `url`) VALUES
(1, 'Xataka', 'http://feeds.weblogssl.com/xataka2'),
(2, 'Andro4All', 'https://feeds.feedburner.com/andro4all'),
(3, 'TecnoLike Plus', 'http://fetchrss.com/rss/5d7945108a93f8666f8b45675ec8a8a48a93f8561a8b4567.xml');

ALTER TABLE `_tecnologia_articles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `title` (`title`);

ALTER TABLE `_tecnologia_sources`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

ALTER TABLE `_tecnologia_articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `_tecnologia_sources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

CREATE TABLE `_tecnologia_comments` (
 `id` int NOT NULL AUTO_INCREMENT,
 `id_person` int NOT NULL,
 `id_article` int DEFAULT NULL,
 `content` varchar(255) NOT NULL,
 `inserted` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`id`)
)