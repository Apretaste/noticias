<?php

use Apretaste\Bucket;
use Apretaste\Images;
use Apretaste\Level;
use Apretaste\Person;
use Apretaste\Request;
use Apretaste\Response;
use Apretaste\Tutorial;
use Apretaste\Challenges;
use Apretaste\Notifications;
use Apretaste\Utils;
use Framework\Crawler;
use Framework\Database;
use Framework\GoogleAnalytics;

class Service
{
	// list of media types
	public static array $mediaTypes = ['politics' => 'Política', 'economy' => 'Economía', 'cultural' => 'Cultura', 'fashion' => 'Moda', 'technology' => 'Tecnología', 'science' => 'Ciencia', 'other' => 'Otros'];

	/**
	 * Get the list of news
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function _main(Request $request, Response $response)
	{
		// get the media selected as prefered
		$preferredMedia = self::getSelectedMedia($request->person);

		// force users to select media
		if (empty($preferredMedia)) {
			return $this->_medios($request, $response);
		}

		// complete tutorial
		Tutorial::complete($request->person->id, 'read_news');

		// create function variables
		$currentPage = $request->input->data->page ?? 1;
		$search = $request->input->data->search ?? false;
		$filters = "";
		$totalPages = 1;
		$searchTags = [];
		$images = [];

		// if is listing the headlines ...
		if (empty($search)) {
			// filter by pagination
			$preferredMedia = implode(',', $preferredMedia);
			$filters = "AND A.media_id IN($preferredMedia)";

			// calculate total number of pages
			$totalPages = Database::queryCache("SELECT COUNT(id) AS total FROM _news_articles WHERE media_id IN($preferredMedia)")[0]->total;
			$totalPages = intval($totalPages / 20) + ($totalPages % 20 > 0 ? 1 : 0);
		} // if searching...
		else {
			// do not search by media and type at the same time
			if (isset($search->media) && isset($search->type)) {
				unset($search->type);
			}

			// create all the filters
			foreach ($search as $field => $value) {
				switch ($field) {
					case 'title':
						$value = preg_replace('!\s+!', ' ', $value);
						$searchTags[] = $value;
						$value = quoted_printable_encode($value);
						$escapedTitle = Database::escape($value);
						$escapedTitle = implode(', ', explode(' ', $escapedTitle));
						$filters .= "AND MATCH(`title`) AGAINST('$escapedTitle') ";
						break;
					case 'media':
						$mediaCaption = Database::queryCache("SELECT caption FROM _news_media WHERE id='$value'", Database::CACHE_YEAR);
						if (!empty($mediaCaption)) $searchTags[] = $mediaCaption[0]->caption;
						$filters .= "AND A.media_id = '$value' ";
						break;
					case 'minDate':
						$searchTags[] = "Despues del $value";
						$filters .= "AND A.pubDate >= STR_TO_DATE('$value','%d/%m/%Y') ";
						break;
					case 'maxDate':
						$searchTags[] = "Antes del $value";
						$filters .= "AND A.pubDate <= STR_TO_DATE('$value','%d/%m/%Y') ";
						break;
					case 'minComments':
						$searchTags[] = "+$value comentarios";
						$filters .= "AND A.comments >= '$value' ";
						break;
					case 'type':
						$searchTags[] = self::$mediaTypes[$value];
						$filters .= "AND B.type = '$value' ";
						break;
					case 'category':
						$categoryCaption = Database::queryCache("SELECT caption FROM _news_categories WHERE id='$value'", Database::CACHE_YEAR);
						if (!empty($categoryCaption)) $searchTags[] = $categoryCaption[0]->caption;
						$filters .= "AND A.category_id = '$value' ";
						break;
					case  'tag':
						$searchTags[] = "#$value";
						$filters .= "AND MATCH(`tags`) AGAINST('$value') ";
						break;
				}
			}
		}

		// create the offet for pagination
		$offset = $currentPage > 1 ? ($currentPage - 1) * 20 : 0;

		// pull the articles to show
		$articles = Database::queryCache("
			SELECT 
				A.id, A.title, A.pubDate, A.author, A.image, A.imageLink, A.media_id,
				A.description, A.comments, A.tags, B.name AS mediaName, 
				B.caption AS mediaCaption, C.caption AS categoryCaption
			FROM _news_articles A 
			LEFT JOIN _news_media B ON A.media_id = B.id 
			LEFT JOIN _news_categories C ON A.category_id = C.id 
			WHERE B.active=true $filters
			ORDER BY pubDate DESC 
			LIMIT 20 OFFSET $offset");

		// format articles
		foreach ($articles as $article) {
			// decode basic tags
			$article->title = quoted_printable_decode($article->title);
			$article->description = quoted_printable_decode($article->description);
			$article->author = quoted_printable_decode($article->author);

			// get the list of tags as an array
			$article->tags = $article->tags ? explode(',', $article->tags) : [];

			if ($article->image) {
				// get the path to the image
				$imgPath = false;

				try {
					$imgPath = Bucket::download($article->mediaName, $article->image);
				} catch(Exception $e) {

				}

				// if the image exists, pull it
				if (file_exists($imgPath)) {
					$image = file_get_contents($imgPath);
				} // if the image do not exist...
				else {
					// try to get it from the internet
					$image = Crawler::get($article->imageLink, 'GET', null, [], [], $info);

					// save the image downloaded
					if ($info['http_code'] ?? 404 === 200 && !empty($image)) {
						$fileName = Utils::randomHash();
						$imgPath = Images::saveBase64Image(base64_encode($image), TEMP_PATH . $fileName);
						$fileName = basename($imgPath);
						if (stripos($fileName, '.') === false) $fileName .= '.jpg';
						Bucket::save($article->mediaName, $imgPath, $fileName);
					}
				}

				// unless there was an error, pull the image
				if (empty($image)) $article->image = false;
				else $images[] = $imgPath;
			}
		}

		// create content for the view
		$content = [
			'articles' => $articles,
			'page' => $currentPage,
			'pages' => $totalPages,
			'searchTags' => $searchTags,
		];

		// send data to the view
		$response->setCache('day');
		$response->setTemplate("titulares.ejs", $content, $images);
	}

	/**
	 * Open a news article
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function _historia(Request $request, Response $response)
	{
		// get link to the article
		$id = $request->input->data->id ?? false;

		// get the details of the article
		$article = Database::queryCache("
			SELECT 
				A.id, A.title, A.pubDate, A.author, A.description, A.media_id, 
				A.category_id, A.image, A.imageCaption, A.content, A.tags,
				B.caption AS source, B.name AS mediaName, C.caption AS categoryCaption
			FROM _news_articles A
			LEFT JOIN _news_media B ON A.media_id = B.id 
			LEFT JOIN _news_categories C ON A.category_id = C.id 
			WHERE A.id = '$id'");

		// return an error if no articles were found
		if (empty($article)) {
			return $response->setTemplate('message.ejs');
		}

		// update the articule views
		$article = $article[0];
		Database::query("UPDATE _news_articles SET views=views+1 WHERE id=$id");

		// decode basic tags
		$article->title = quoted_printable_decode($article->title);
		$article->description = quoted_printable_decode($article->description);
		$article->content = quoted_printable_decode($article->content);
		$article->imageCaption = quoted_printable_decode($article->imageCaption);

		// get the image, if exists
		$images = [];
		if ($article->image) {
			try {
				$images[] = Bucket::download($article->mediaName, $article->image);
			} catch(Exception $e) {

			}
		}

		// get the comments of the article
		$article->comments = Database::query("
			SELECT 
				A.id, A.content, A.inserted, A.likes, A.unlikes, B.username, B.avatar, B.avatarColor, B.gender,
				IF(A.id_person = {$request->person->id}, 'right', 'left') AS position
			FROM _news_comments A 
			LEFT JOIN person B ON A.id_person = B.id 
			WHERE A.id_article='{$article->id}' 
			ORDER BY A.id DESC");

		// make tags lowercase and remove tildes
		$article->tags = preg_replace('/&([^;])[^;]*;/', "$1", htmlentities(mb_strtolower($article->tags), null));

		// get similar articles
		$article->similars = Database::queryCache("
			SELECT id, title, tags 
			FROM _news_articles 
			WHERE MATCH(`tags`) AGAINST('{$article->tags}') 
			AND id <> {$article->id} 
			LIMIT 2");

		// for every similar article...
		foreach ($article->similars as $similar) {
			// decode title
			$similar->title = quoted_printable_decode($similar->title);

			// make tags lowercase and remove tildes
			$similar->tags = preg_replace('/&([^;])[^;]*;/', "$1", htmlentities(mb_strtolower($similar->tags), null));

			// remove tags that show on the article
			$similarTags = array_intersect(explode(',', $article->tags), explode(',', $similar->tags));

			// make first letter capital 
			$similar->tags = [];
			foreach ($similarTags as $tag) {
				$similar->tags[] = ucfirst($tag);
			}
		}

		// create the rest of the content
		$article->isGuest = $request->person->isGuest;
		$article->username = $request->person->username;
		$article->gender = $request->person->gender;
		$article->avatar = $request->person->avatar;
		$article->avatarColor = $request->person->avatarColor;

		// set category as false if not exist
		if (empty($article->category_id)) {
			$article->category_id = false;
			$article->categoryCaption = false;
		}

		// complete the challenge
		Challenges::complete('read-news', $request->person->id);

		// submit to Google Analytics
		GoogleAnalytics::event('news_read_medium', $article->mediaName);

		// send info to the view
		$response->setCache('year');
		$response->setTemplate('historia.ejs', $article, $images);
	}

	/**
	 * Opens the search screen
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function _buscar(Request $request, Response $response)
	{
		// search for the media available
		$availableMedia = Database::queryCache("SELECT id, caption, `type` FROM _news_media WHERE active=true");

		// create content for the view
		$content = [
			'availableMedia' => $availableMedia,
			'mediaTypes' => self::$mediaTypes
		];

		// send data to the view
		$response->setCache('year');
		$response->setTemplate('buscar.ejs', $content);
	}

	/**
	 * List the news channels opened
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function _medios(Request $request, Response $response)
	{
		// get the media selected as prefered
		$preferredMedia = self::getSelectedMedia($request->person);

		// get the list of media
		$availableMedia = Database::queryCache("SELECT * FROM _news_media WHERE active=true");

		// create content for the view
		$content = [
			'availableMedia' => $availableMedia,
			'preferredMedia' => $preferredMedia,
			'mediaTypes' => self::$mediaTypes
		];

		// send data to the view
		$response->setTemplate('media.ejs', $content);
	}

	/**
	 * Watch the list of latest comments
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function _comentarios(Request $request, Response $response)
	{
		// get the media selected as prefered
		$preferredMedia = self::getSelectedMedia($request->person);

		// force users to select media
		if (empty($preferredMedia)) {
			return $this->_medios($request, $response);
		}

		// get all the comments
		$comments = Database::query("
			SELECT 
				A.id, A.id_article, A.content, A.inserted, A.likes, A.unlikes,
				B.username, B.avatar, B.avatarColor, B.gender,
				C.title, C.pubDate, C.author, D.caption AS mediaCaption,
				IF(A.id_person = {$request->person->id}, 'right', 'left') AS position
			FROM _news_comments A 
			LEFT JOIN person B ON A.id_person = B.id 
			LEFT JOIN _news_articles C ON C.id = A.id_article
			LEFT JOIN _news_media D ON D.id = C.media_id 
			WHERE D.active=true
			ORDER BY A.inserted DESC LIMIT 20");

		// decode title for the comments
		foreach ($comments as $comment) {
			$comment->title = quoted_printable_decode($comment->title);
		}

		// create content for the view
		$content = [
			"comments" => $comments,
			"isGuest" => $request->person->isGuest,
			'username' => $request->person->username,
			'gender' => $request->person->gender,
			'avatar' => $request->person->avatar,
			'avatarColor' => $request->person->avatarColor
		];

		// send info to the view
		$response->setCache('day');
		$response->setTemplate("comentarios.ejs", $content);
	}

	/**
	 * Save the news channels selected by the user
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function _guardar(Request $request, Response $response)
	{
		// get the ids to save
		$ids = $request->input->data->ids ?? false;

		// save the new media for the user
		if ($ids) {
			$ids = implode(',', $ids);
			Database::query("
				INSERT INTO _news_preferences VALUES ({$request->person->id}, '$ids')
				ON DUPLICATE KEY UPDATE selected_media='$ids'");
		}

		// redirect to titulares
		$this->_main($request, $response);
	}

	/**
	 * Comment on an article, or on the main feed
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws \Framework\Alert
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

		// notify users mentioned
		$mentions = $this->findUsersMentionedOnText($comment, $request->person->id);
		$mentionText = "@{$request->person->username} le ha mencionado en una noticia";
		$mentionLink = "{'command':'NOTICIAS HISTORIA', 'data':{'id':'$articleId'}}";

		if ($articleId) {
			// check the note ID is valid
			$article = Database::queryFirst("SELECT COUNT(*) AS total FROM _news_articles WHERE id = '$articleId'");
			if ($article->total == "0") return;

			// escape comment data
			$comment = Database::escape($comment, 255);

			// save the comment and increase the article comments
			Database::query("
				INSERT INTO _news_comments (id_person, id_article, content) VALUES ('{$request->person->id}', '$articleId', '$comment');
				UPDATE _news_articles SET comments = comments + 1 WHERE id = '$articleId';");

			// add the experience
			Level::setExperience('NEWS_COMMENT_FIRST_DAILY', $request->person->id);

			// complete challenge
			Challenges::complete('comment-news', $request->person->id);

			// submit to Google Analytics
			GoogleAnalytics::event('news_comment', $articleId);
		} else {
			// insert comment without article
			Database::query("
				INSERT INTO _news_comments (id_person, content) 
				VALUES ('{$request->person->id}', '$comment')");

			$mentionText = "@{$request->person->username} le ha mencionado en un comentario en noticias";
			$mentionLink = "{'command':'NOTICIAS COMENTARIOS'}";

			// submit to Google Analytics
			GoogleAnalytics::event('news_comment', 'none');
		}

		foreach ($mentions as $mentionId) {
			Notifications::alert(
				$mentionId, $mentionText, 'comment',
				$mentionLink
			);
		}
	}

	/**
	 * The user likes a note
	 *
	 * @param Request $request
	 * @param Response $response
	 * @author salvipascual
	 */
	public function _like(Request $request, Response $response)
	{
		$type = 'comment';
		$actionsTable = '_news_comments_actions';
		$commentsTable = '_news_comments';
		$commentId = $request->input->data->id;

		if ($commentId === 'last') {
			$commentId = Database::query("SELECT MAX(id) AS id FROM $commentsTable WHERE id_person = '{$request->person->id}'")[0]->id;
		}

		// check if the user already liked this note
		$res = Database::query("SELECT * FROM $actionsTable WHERE id_person={$request->person->id} AND $type='{$commentId}'");
		$comment = Database::query("SELECT id_person, likes FROM $commentsTable WHERE id='{$commentId}'");

		if (!empty($comment)) {
			$comment = $comment[0];

			if (!empty($res)) {
				if ($res[0]->action === 'unlike') {
					// update previous vote
					Database::query("
						UPDATE $actionsTable SET `action`='like' WHERE id_person='{$request->person->id}' AND $type='{$commentId}';
						UPDATE $commentsTable SET likes=likes+1, unlikes=unlikes-1 WHERE id='{$commentId}'"
					);

					return;
				} else if ($res[0]->action === 'like') return;
			}


			// add new vote
			Database::query("
				INSERT INTO $actionsTable (id_person,$type,action) VALUES ('{$request->person->id}','{$commentId}','like');    
				UPDATE $commentsTable SET likes=likes+1 WHERE id='{$commentId}';"
			);

		}
	}

	/**
	 * The user unlikes a note
	 *
	 * @param Request $request
	 * @param Response $response
	 * @author salvipascual
	 */
	public function _unlike(Request $request, Response $response): void
	{
		$type = 'comment';
		$actionsTable = '_news_comments_actions';
		$commentsTable = '_news_comments';
		$commentId = $request->input->data->id;

		if ($commentId === 'last') {
			$commentId = Database::query("SELECT MAX(id) AS id FROM $commentsTable WHERE id_person = '{$request->person->id}'")[0]->id;
		}

		// check if the user already liked this note
		$res = Database::query("SELECT * FROM $actionsTable WHERE id_person={$request->person->id} AND $type='{$commentId}'");
		$comment = Database::query("SELECT id_person FROM $commentsTable WHERE id='{$commentId}'");

		// do not continue if note do not exist
		if (empty($comment)) {
			return;
		}

		// delete previos upvote and add new vote
		if (!empty($res)) {
			if ($res[0]->action === 'like') {
				Database::query("
				UPDATE $actionsTable SET `action`='unlike' WHERE id_person='{$request->person->id}' AND $type='{$commentId}';
				UPDATE $commentsTable SET likes=likes-1, unlikes=unlikes+1 WHERE id='{$commentId}';");
			}
			return;
		}

		// delete previos vote and add new vote
		Database::query("
			INSERT INTO $actionsTable (id_person,$type,action) VALUES ('{$request->person->id}','{$commentId}','unlike');
			UPDATE $commentsTable SET unlikes=unlikes+1 WHERE id='{$commentId}';"
		);
	}

	/**
	 * Get the list of news media the user is reading
	 *
	 * @param Person $person
	 * @return Array
	 */
	private static function getSelectedMedia(Person $person)
	{
		// get the CSV of media ID selected
		$selectedMedia = Database::queryFirst("SELECT selected_media FROM _news_preferences WHERE person_id={$person->id}");

		// convert the CSV to an array of IDs, and return
		return empty($selectedMedia->selected_media) ? [] : explode(',', $selectedMedia->selected_media);
	}


	/**
	 * Find all mentions on a text
	 *
	 * @param String $text
	 * @param String $myId
	 * @return array, [userId]
	 * @throws \Framework\Alert
	 * @author ricardo
	 */

	private function findUsersMentionedOnText(string $text, string $myId): array
	{
		// find all users mentioned
		preg_match_all('/@\w*/', $text, $matches);

		// filter the ones that exist
		$mentions = [];
		if (!empty($matches[0])) {
			// get string of possible matches
			$usernames = "'" . implode("','", $matches[0]) . "'";
			$usernames = str_replace('@', '', $usernames);
			$usernames = str_replace(",'',", ',', $usernames);
			$usernames = str_replace(",''", '', $usernames);
			$usernames = str_replace("'',", '', $usernames);

			// check real matches against the database
			$users = Database::query("SELECT id FROM person WHERE username in ($usernames)");

			// format the return
			foreach ($users as $user) {
				if ($user->id != $myId) $mentions[] = $user->id;
			}
		}

		return $mentions;
	}
}
