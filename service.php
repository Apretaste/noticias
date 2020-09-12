<?php

use Apretaste\Level;
use Apretaste\Person;
use Apretaste\Request;
use Apretaste\Response;
use Framework\Alert;
use Framework\Crawler;
use Framework\Database;
use Apretaste\Challenges;

class Service
{
	public static array $mediaTypes = [
		'politics' => 'Política', 'economy' => 'Economía', 'cultural' => 'Cultura',
		'fashion' => 'Moda', 'technology' => 'Tecnología', 'science' => 'Ciencia', 'other' => 'Otros'
	];

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
		} else {
			$preferredMedia = implode(',', $preferredMedia);
		}

		$currentPage = $request->input->data->page ?? 1;
		$mediaWhere = "WHERE A.media_id IN($preferredMedia)";
		$offset = $currentPage > 1 ? ($currentPage - 1) * 20 : 0;

		$totalPages = 1;

		$search = $request->input->data->search ?? false;
		$searchTags = [];

		if ($search && !empty($search)) {
			$mediaWhere = "WHERE 1 ";
			if (isset($search->media) && isset($search->type)) {
				unset($search->type);
			}
			foreach ($search as $field => $value) {
				switch ($field) {
					case 'title':
						$value = preg_replace('!\s+!', ' ', $value);
						$searchTags[] = $value;
						$value = quoted_printable_encode($value);
						$escapedTitle = Database::escape($value);
						$escapedTitle = implode(', ', explode(' ', $escapedTitle));

						$mediaWhere .= "AND MATCH(`title`) AGAINST('$escapedTitle') ";
						break;
					case 'media':
						$mediaCaption = Database::queryCache("SELECT caption FROM _news_media WHERE id='$value'", Database::CACHE_YEAR);
						if (!empty($mediaCaption)) {
							$searchTags[] = $mediaCaption[0]->caption;
						}
						$mediaWhere .= "AND A.media_id = '$value' ";
						break;
					case 'minDate':
						$searchTags[] = "Despues del $value";
						$mediaWhere .= "AND A.pubDate >= STR_TO_DATE('$value','%d/%m/%Y') ";
						break;
					case 'maxDate':
						$searchTags[] = "Antes del $value";
						$mediaWhere .= "AND A.pubDate <= STR_TO_DATE('$value','%d/%m/%Y') ";
						break;
					case 'minComments':
						$searchTags[] = "+$value comentarios";
						$mediaWhere .= "AND A.comments >= '$value' ";
						break;
					case 'type':
						$searchTags[] = ucfirst($value);
						$mediaWhere .= "AND B.type = '$value' ";
						break;
					case 'category':
						$categoryCaption = Database::queryCache("SELECT caption FROM _news_categories WHERE id='$value'", Database::CACHE_YEAR);
						if (!empty($categoryCaption)) {
							$searchTags[] = $categoryCaption[0]->caption;
						}
						$mediaWhere .= "AND A.category_id = '$value' ";
						break;
				}
			}
		} else {
			$totalPages = Database::queryFirst("SELECT COUNT(id) AS total FROM _news_articles WHERE media_id IN($preferredMedia)")->total;
			$totalPages = intval($totalPages / 20) + ($totalPages % 20 > 0 ? 1 : 0);
			;
		}

		$articles = Database::queryCache(
			"SELECT A.id, A.title, A.pubDate, A.author, A.image, A.imageLink, 
				A.description, A.comments, A.tags, B.name AS mediaName, B.caption AS mediaCaption, C.caption AS categoryCaption
				FROM _news_articles A LEFT JOIN _news_media B ON A.media_id = B.id 
				LEFT JOIN _news_categories C ON A.category_id = C.id 
				$mediaWhere ORDER BY pubDate DESC LIMIT 20 OFFSET $offset"
		);

		$inCuba = $request->input->inCuba ?? false;
		$serviceImgPath = SERVICE_PATH . "{$request->input->service}/images";
		$images = ["$serviceImgPath/no-image.png"];
		$techImgDir = SHARED_PUBLIC_PATH . 'content/news';

		foreach ($articles as $article) {
			$article->title = quoted_printable_decode($article->title);
			$article->description = quoted_printable_decode($article->description);
			$article->author = quoted_printable_decode($article->author);
			$article->tags = $article->tags ? explode(',', $article->tags) : [];

			if (!$inCuba) {
				if ($article->image) {
					$imgPath = "$techImgDir/{$article->mediaName}/images/{$article->image}";

					if (!file_exists($imgPath)) {
						$image = Crawler::get($article->imageLink, 'GET', null, [], [], $info);

						if ($info['http_code'] ?? 404 === 200) {
							if (!empty($image)) {
								file_put_contents($imgPath, $image);
							}
						}
					} else {
						$image = file_get_contents($imgPath);
					}

					if (!empty($image)) {
						$images[] = $imgPath;
					}
				}
			} else {
				$article->image = "no-image.png";
			}
		}

		// Search info
		$availableMedia = Database::queryCache("SELECT id, caption, `type` FROM _news_media");

		/*foreach ($availableMedia as $media) { // To search in specific media categories
			$categories = Database::queryCache("SELECT id, caption FROM _news_categories WHERE media_id={$media->id}");
			$media->categories = $categories;
		}*/

		$content = [
			"articles" => $articles,
			'isGuest' => $request->person->isGuest, 'title' => "Titulares",
			'page' => $currentPage, 'pages' => $totalPages,
			'availableMedia' => $availableMedia, 'mediaTypes' => self::$mediaTypes,
			'searchTags' => $searchTags
		];

		// send data to the view
		$response->setCache(60);
		$response->setLayout('noticias.ejs');
		$response->setTemplate("stories.ejs", $content, $images);
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
			$article = Database::queryCache("SELECT A.*, B.caption AS source, B.name AS mediaName, C.caption AS categoryCaption FROM _news_articles A LEFT JOIN _news_media B ON A.media_id = B.id LEFT JOIN _news_categories C ON A.category_id = C.id WHERE A.id='$id'")[0];
			Database::query("UPDATE _news_articles SET views=views+1 WHERE id=$id");

			$article->title = quoted_printable_decode($article->title);
			$article->description = quoted_printable_decode($article->description);
			$article->content = quoted_printable_decode($article->content);
			$article->imageCaption = quoted_printable_decode($article->imageCaption);
			$article->comments = Database::query("SELECT A.*, B.username, B.avatar, B.avatarColor, B.gender FROM _news_comments A LEFT JOIN person B ON A.id_person = B.id WHERE A.id_article='{$article->id}' ORDER BY A.id DESC");

			foreach ($article->comments as $comment) {
				$comment->avatar = $comment->avatar ?? ($comment->gender === 'F' ? 'chica' : 'hombre');
				$comment->position = $comment->id_person == $request->person->id ? 'right' : 'left';
			}

			$article->similars = Database::queryCache("SELECT id, title, tags FROM _news_articles WHERE MATCH(`tags`) AGAINST('{$article->tags}') AND id <> {$article->id} LIMIT 2");

			foreach ($article->similars as $similar) {
				$similar->title = quoted_printable_decode($similar->title);

				// To lower and without tildes
				$similar->tags = preg_replace('/&([^;])[^;]*;/', "$1", htmlentities(mb_strtolower($similar->tags), null));
				$article->tags = preg_replace('/&([^;])[^;]*;/', "$1", htmlentities(mb_strtolower($article->tags), null));
				;

				$similarTags = array_intersect(explode(',', $article->tags), explode(',', $similar->tags));

				$similar->tags = [];
				foreach ($similarTags as $tag) {
					$similar->tags[] = ucfirst($tag);
				}
			}

			$article->isGuest = $request->person->isGuest;
			$article->barTitle = "Noticias";
			$article->username = $request->person->username;
			$article->avatar = $request->person->avatar;
			$article->avatarColor = $request->person->avatarColor;
			$article->gender = $request->person->gender;

			$images = [];

			// get the image if exist
			$techImgDir = SHARED_PUBLIC_PATH . 'content/news';
			if (!empty($article->image)) {
				$images[] = "$techImgDir/{$article->mediaName}/images/{$article->image}";
			}

			Challenges::complete('read-news', $request->person->id);

			// send info to the view
			$response->setCache('30');
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
			return;
		}

		$preferredMedia = $request->emptyMedia ?? self::getSelectedMedia($request->person);
		$availableMedia = Database::queryCache("SELECT * FROM _news_media");

		$images = [];
		foreach ($availableMedia as $media) {
			$images[] = SERVICE_PATH . "/noticias/images/{$media->logo}";
		}

		$content = [
			'availableMedia' => $availableMedia,
			'preferredMedia' => $preferredMedia,
			'mediaTypes' => self::$mediaTypes,
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
			'gender' => $request->person->gender,
			'avatar' => $request->person->avatar,
			'avatarColor' => $request->person->avatarColor,
			'title' => 'Comentarios'
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
			if ($article[0]->total == "0") {
				return;
			}

			// save the comment
			$comment = Database::escape($comment, 255);
			Database::query("
				INSERT INTO _news_comments (id_person, id_article, content) VALUES ('{$request->person->id}', '$articleId', '$comment');
				UPDATE _news_articles SET comments = comments+1 WHERE id='$articleId';");

			// add the experience
			Level::setExperience('NEWS_COMMENT_FIRST_DAILY', $request->person->id);

			Challenges::complete('comment-news', $request->person->id);
		} else {
			Database::query("INSERT INTO _news_comments (id_person, content) VALUES ('{$request->person->id}', '$comment')");
		}
	}

	private static function getSelectedMedia(Person $person)
	{
		$selectedMedia = Database::queryFirst("SELECT selected_media FROM _news_preferences WHERE person_id={$person->id}");


		if ($selectedMedia && $selectedMedia->selected_media != "") {
			$selectedMedia = explode(',', $selectedMedia->selected_media);
		} else {
			$selectedMedia = [];
		}

		return $selectedMedia;
	}

	private static function saveSelectedMedia(Person $person, $media)
	{
		$media = implode(',', $media);
		Database::query("INSERT INTO _news_preferences VALUES({$person->id}, '$media') ON DUPLICATE KEY UPDATE selected_media='$media'");
	}
}
