<?php

use Apretaste\Level;
use Apretaste\Person;
use Apretaste\Request;
use Apretaste\Response;
use Framework\Alert;
use Framework\Crawler;
use Framework\Database;

class Service
{
	// TODO add https://es.digitaltrends.com/fuentes-rss/

	/**
	 * Get the list of news
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 * @author ricardo
	 */
	public function _main(Request $request, Response &$response)
	{
		$preferredMedia = self::getSelectedMedia($request->person);
		if (empty($preferredMedia)) {
			$request->preferredMedia = $preferredMedia;
			$this->_medios($request, $response);
			return;
		}

		$selectedMedia = $request->input->data->media ?? false;
		$currentPage = $request->input->data->page ?? 1;
		$mediaWhere = $selectedMedia ? "WHERE A.media_id = $selectedMedia" : "";
		$offset = $currentPage > 1 ? ($currentPage - 1) * 20 : 0;

		$totalPages = Database::queryFirst("SELECT COUNT(id) AS total FROM _news_articles")->total;

		$articles = Database::query(
			"SELECT A.id, A.title, A.pubDate, A.author, A.image, A.imageLink, 
				A.description, A.comments, A.tags, B.name AS mediaName, B.caption AS mediaCaption, C.caption AS categoryCaption
				FROM _news_articles A LEFT JOIN _news_media B ON A.media_id = B.id 
				LEFT JOIN _news_categories C ON A.category_id = C.id 
				$mediaWhere ORDER BY pubDate DESC LIMIT 20 OFFSET $offset"
		);

		$inCuba = $request->input->inCuba ?? false;
		$serviceImgPath = SERVICE_PATH . "news/images";
		$images = ["$serviceImgPath/no-image.png"];
		$techImgDir = SHARED_PUBLIC_PATH . 'content/news';

		foreach ($articles as $article) {
			$article->title = quoted_printable_decode($article->title);
			$article->description = quoted_printable_decode($article->description);
			$article->author = quoted_printable_decode($article->author);

			$article->tags = $article->tags ? explode(',', $article->tags) : [];

			if (!$inCuba) {
				$imgPath = "$techImgDir/{$article->mediaName}/images/{$article->image}";

				if (!file_exists($imgPath)) {
					$image = Crawler::get($article->imageLink, 'GET', null, [], [], $info);

					if ($info['http_code'] ?? 404 === 200)
						if (!empty($image))
							file_put_contents($imgPath, $image);
				} else {
					$image = file_get_contents($imgPath);
				}

				if (!empty($image)) $images[] = $imgPath;
			} else {
				$article->image = "no-image.png";
			}
		}

		$content = [
			"articles" => $articles, "selectedMedia" => $selectedMedia,
			'isGuest' => $request->person->isGuest, 'title' => "Titulares",
			'page' => $currentPage, 'pages' => $totalPages
		];

		// send data to the view
		$response->setCache(60);
		$response->setLayout('noticias.ejs');
		$response->setTemplate("stories.ejs", $content, $images);
	}

	private static function toEspMonth(string $date)
	{
		$months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
		$espMonths = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

		return str_replace($months, $espMonths, $date);
	}

	/**
	 * Call to show the news
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 */
	public function _historia(Request $request, Response $response)
	{
		$preferredMedia = self::getSelectedMedia($request->person);
		if (empty($preferredMedia)) {
			$request->preferredMedia = $preferredMedia;
			$this->_medios($request, $response);
			return;
		}

		// get link to the article
		$id = $request->input->data->id ?? false;

		if ($id) {
			$article = Database::query("SELECT A.*, B.caption AS source, C.caption AS categoryCaption FROM _news_articles A LEFT JOIN _news_media B ON A.media_id = B.id LEFT JOIN _news_categories C ON A.category_id = C.id WHERE A.id='$id'")[0];

			$article->title = quoted_printable_decode($article->title);
			$article->description = quoted_printable_decode($article->description);
			$article->content = quoted_printable_decode($article->content);
			$article->imageCaption = quoted_printable_decode($article->imageCaption);
			$article->comments = Database::query("SELECT A.*, B.username, B.avatar, B.avatarColor, B.gender FROM _news_comments A LEFT JOIN person B ON A.id_person = B.id WHERE A.id_article='{$article->id}' ORDER BY A.id DESC");

			foreach ($article->comments as $comment) {
				$comment->avatar = $comment->avatar ?? ($comment->gender === 'F' ? 'chica' : 'hombre');
				$comment->position = $comment->id_person == $request->person->id ? 'right' : 'left';
			}

			$article->isGuest = $request->person->isGuest;
			$article->barTitle = "Noticias";
			$article->username = $request->person->username;
			$article->avatar = $request->person->avatar;
			$article->avatarColor = $request->person->avatarColor;
			$article->gender = $request->person->gender;

			$images = [];

			// get the image if exist
			$source = str_replace(' ', '_', $article->source);
			$techImgDir = SHARED_PUBLIC_PATH . 'content/news';
			if (!empty($article->image)) $images[] = "$techImgDir/{$source}/{$article->image}";

			// send info to the view
			$response->setCache('30');
			$response->setLayout('noticias.ejs');
			$response->setTemplate('story.ejs', $article, $images);
		} else {
			$this->error($response, "Articulo no encontrado", "No sabemos que articulo estas buscando");
			return;
		}
	}

	/**
	 * Call to show the news
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 */

	public function _medios(Request $request, Response $response)
	{
		$preferredMedia = $request->input->data->preferredMedia ?? false;

		if ($preferredMedia !== false) {
			self::saveSelectedMedia($request->person, $preferredMedia);
		}

		$preferredMedia = $request->emptyMedia ?? self::getSelectedMedia($request->person);
		$availableMedia = Database::query("SELECT * FROM _news_media");
		$mediaTypes = [
			'politics' => 'Política', 'economy' => 'Economía', 'cultural' => 'Cultura',
			'fashion' => 'Moda', 'technology' => 'Tecnología', 'science' => 'Ciencia', 'other' => 'Otros'
		];

		$images = [];
		foreach ($availableMedia as $media) {
			$images[] = SHARED_PUBLIC_PATH . "/content/news/{$media->name}/{$media->logo}";
		}

		$content = [
			'availableMedia' => $availableMedia,
			'preferredMedia' => $preferredMedia,
			'mediaTypes' => $mediaTypes,
			'title' => 'Medios'
		];

		$response->setLayout('noticias.ejs');
		$response->setTemplate('media.ejs', $content, $images);
	}

	/**
	 * Return an error message
	 *
	 * @param Response $response
	 * @param String $title
	 * @param String $desc
	 * @return Response
	 * @throws Alert
	 * @author ricardo
	 */
	private function error(Response $response, $title, $desc)
	{
		// display show error in the log
		error_log("[NOTICIAS] $title | $desc");

		// return error template
		$response->setLayout('noticias.ejs');
		return $response->setTemplate('message.ejs', ["header" => $title, "text" => $desc, 'barTitle' => "Lo sentimos"]);
	}

	/**
	 * Watch the last comments in articles or with no article
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws Alert
	 */

	public function _comentarios(Request $request, Response $response)
	{
		$preferredMedia = self::getSelectedMedia($request->person);
		if (empty($preferredMedia)) {
			$request->preferredMedia = $preferredMedia;
			$this->_medios($request, $response);
			return;
		}

		$comments = Database::query("SELECT A.*, B.username, B.avatar, B.avatarColor, B.gender, C.title, C.pubDate, C.author, D.caption AS mediaCaption FROM _news_comments A LEFT JOIN person B ON A.id_person = B.id LEFT JOIN _news_articles C ON C.id = A.id_article LEFT JOIN _news_media D ON D.id = C.media_id ORDER BY A.id DESC LIMIT 20");

		foreach ($comments as $comment) {
			$comment->title = quoted_printable_decode($comment->title);
			$comment->avatar = $comment->avatar ?? ($comment->gender === 'F' ? 'chica' : 'hombre');
		}

		$content = [
			"comments" => $comments,
			"isGuest" => $request->person->isGuest,
			'barTitle' => "Comentarios",
			'username' => $request->person->username,
			'avatar' => $request->person->avatar,
			'avatarColor' => $request->person->avatarColor
		];

		// send info to the view
		$response->setLayout('noticias.ejs');
		$response->setTemplate("comments.ejs", $content);
	}

	/**
	 * Comment an article
	 *
	 * @param Request $request
	 * @param Response $response
	 *
	 * @throws Exception
	 * @author ricardo
	 *
	 */
	public function _comentar(Request $request, Response $response)
	{
		// do not allow guest comments
		if ($request->person->isGuest) {
			return;
		}

		// get comment data
		$comment = $request->input->data->comment;
		$articleId = $request->input->data->article ?? false;

		if ($articleId) {
			// check the note ID is valid
			$article = Database::query("SELECT COUNT(*) AS total FROM _news_articles WHERE id='$articleId'");
			if ($article[0]->total == "0") return;

			// save the comment
			$comment = Database::escape($comment, 255);
			Database::query("
				INSERT INTO _news_comments (id_person, id_article, content) VALUES ('{$request->person->id}', '$articleId', '$comment');
				UPDATE _news_articles SET comments = comments+1 WHERE id='$articleId';");

			// add the experience
			Level::setExperience('NEWS_COMMENT_FIRST_DAILY', $request->person->id);
		} else {
			Database::query("INSERT INTO _news_comments (id_person, content) VALUES ('{$request->person->id}', '$comment')");
		}
	}

	private static function getSelectedMedia(Person $person)
	{
		$selectedMedia = Database::queryFirst("SELECT selected_media FROM _news_preferences WHERE person_id={$person->id}");


		if ($selectedMedia && $selectedMedia->selected_media != "") {
			$selectedMedia = explode(',', $selectedMedia->selected_media);
		} else $selectedMedia = [];

		return $selectedMedia;
	}

	private static function saveSelectedMedia(Person $person, $media)
	{
		$media = implode(',', $media);
		Database::query("INSERT INTO _news_preferences VALUES({$person->id}, '$media') ON DUPLICATE KEY UPDATE selected_media='$media'");
	}
}
