<?php
/*
Parser holds a variety of functions made to parse the various attributes of an artifact (typically into the corresponding html).
It features a series of generalized formatting functions.

In the event of an expansion or customization of this system,
new parsing criteria and features can seamlessly be introduced as a basic addition to the existing code.
*/
class Parser {
	//directories
	private $imageDirectory = 'media/images';
	private $soundDirectory = 'media/sounds';
	private $videoDirectory = 'media/videos';

	//goes through all artifact attributes that are independant from other artifacts and formats each one according to existing formatting rules
	public function firstFormat($artifact) {
		//trim tags
		if ($artifact->tags) {
			for ($i = 0; $i < sizeof($artifact->tags); $i++) {
				$artifact->tags[$i] = trim($artifact->tags[$i]);
			}
		}

		//format links
		if ($artifact->links) {
			$newLinks = array();
			for ($i = 0; $i < sizeof($artifact->links); $i++) {
				$parts = explode('>', $artifact->links[$i]);
				$parts[0] = trim($parts[0]);
				$parts[1] = trim($parts[1]);
				array_push($newLinks, '<a href="' . $parts[1] . '"><span>' . $parts[0] . '</span></a>');
			}
			$artifact->links = $newLinks;
		}

		//create image
		if($artifact->attributes['image']) $artifact->attributes['image'] = $this->createImagePath($artifact->attributes['image']);

		//format image name
		if ($artifact->attributes['image name']) $artifact->attributes['image name'] = $this->formatTitle($artifact->attributes['image name']);

		//format title
		if ($artifact->attributes['title']) {
			$this->formatText($artifact, 'title', '@');
			$this->formatText($artifact, 'title', '#');
			$this->formatText($artifact, 'title', '_');
			$this->formatText($artifact, 'title', '*');
			$this->formatText($artifact, 'title', '$');
			$this->formatText($artifact, 'title', '>');
		}

		//format content
		if ($artifact->attributes['content']) {
			$this->formatText($artifact, 'content', '@');
			$this->formatText($artifact, 'content', '#');
			$this->formatText($artifact, 'content', '_');
			$this->formatText($artifact, 'content', '*');
			$this->formatText($artifact, 'content', '~');
			$this->formatText($artifact, 'content', '?');
			$this->formatText($artifact, 'content', '&');
			$this->formatText($artifact, 'content', '^');
			$this->formatText($artifact, 'content', '<');
			$this->formatText($artifact, 'content', '%');
			$this->formatText($artifact, 'content', '!');
			$this->formatText($artifact, 'content', '>');
		}
	}

	//goes through remaining artifact attributes (ones that are dependant on the previous formats being complete) and formats each one according to the existing formatting rules
	public function secondFormat($artifact) {
		//format content
		if ($artifact->attributes['content']) {

			//reformat 10 times to allow for 10 levels of nested artifact content retrieval
			//could be theoretically infinite, but that doesn't seem practical, and will easily end up with infinite loops
			for ($i = 0; $i < 10; $i++) {
				$this->formatText($artifact, 'content', '-');
				$this->formatText($artifact, 'content', '=');
				$this->formatText($artifact, 'content', '$');
			}
			
			//clean paragraphs
			$artifact->attributes['content'] = $this->cleanParagraphs($artifact->attributes['content']);
		}
	}

	//finds all instances of $symbol[] within $artifact->attributes[$attribute], and replaces it with the appropriate html element, and applies custom $style to said element
	//manages nested brackets
	private function formatText($artifact, $attribute, $symbol) {
		//check open vs closed brackets by using counter to match corresponding brackets
		//if number of opening brackets and closing brackets is uneven count, display error
		if (sizeof($this->allStringPositions($artifact->attributes[$attribute], '[')) != sizeof($this->allStringPositions($artifact->attributes[$attribute], ']'))) {
			$artifact->attributes['image'] = null;
			$artifact->attributes['image name'] = null;
			$artifact->attributes['content'] = null;
			$artifact->borkenPath = null;
			$artifact->path = null;
			$artifact->tags = null;
			$artifact->attributes['title'] = 'There was an error loading this page.';
			return;
		}

		//get first instance of '$symbol['
		$position = strpos($artifact->attributes[$attribute], $symbol.'[');

		while ($position !== false) {
			//find closing ']'
			$end = strpos($artifact->attributes[$attribute], ']', $position);

			//check if any other '[]' pairs exist within substring, suggesting we haven't found the proper ']'
			//find next ']' until we've found the proper ']'
			while (sizeof($this->allStringPositions(substr($artifact->attributes[$attribute], $position, $end - $position + 1), '[')) != sizeof($this->allStringPositions(substr($artifact->attributes[$attribute], $position, $end - $position + 1), ']'))) {
				$end = strpos($artifact->attributes[$attribute], ']', $end + 1);
			}

			//depending on $symbol, run proper format rule
			$string = substr($artifact->attributes[$attribute], $position, $end - $position + 1);
			switch ($symbol) {
				case '!':
					$new = $this->createSubtitle($string);
					break;
				
				case '&':
					$new = $this->createImage($string);
					break;

				case '^':
					$new = $this->createAudio($string);
					break;

				case '<':
					$new = $this->createVideo($string);
					break;

				case '#':
					$new = $this->createLink($string);
					break;

				case '*':
					$new = $this->createBold($string);
					break;

				case '_':
					$new = $this->createItalic($string);
					break;

				case '%':
					$new = $this->createDivider($string);
					break;

				case '$':
					$new = $this->createReference($string);
					break;

				case '@':
					$new = $this->createCustomLink($string);
					break;

				case '-':
					$new = $this->createSpaciousList($string);
					break;

				case '=':
					$new = $this->createCondensedList($string);
					break;

				case '~':
					$new = $this->createNote($string);
					break;

				case '?':
					$new = $this->createQuote($string);
					break;

				case '>':
					$new = $this->executePHP($string);
					break;

				default:
					return;
					break;
			}
			//replace attribute with formatted attribute
			$artifact->attributes[$attribute] = str_replace($string, $new, $artifact->attributes[$attribute]);
			//find next '$symbol[' to parse
			$position = strpos($artifact->attributes[$attribute], $symbol.'[');
		}
	}

	//header title
	private function formatTitle($string) {
		global $artifacts;

		$string = $this->cleanString($string);
		if ($this->artifactExist($string)) return '<a href="'.strtolower($string).'" class="header-title">'.$string.'</a>';
		return '<span class="header-title">'.$string.'</span>';
	}

	//link
	private function createLink($string) {
		global $artifacts;

		$string = $this->cleanString($string);
		if ($this->artifactExist($string)) return '<a href="'.strtolower($string).'">'.$string.'</a>';
		return '<span>'.$string.'</span>';
	}

	//custom link
	private function createCustomLink($string) {
		global $artifacts;

		$string = $this->cleanString($string);
		$accessor = strpos($string, '>');

		//if accessor not found, return empty
		if ($accessor == false) return '';

		$word = trim(substr($string, 0, $accessor));
		$link = trim(substr($string, $accessor + 1, strlen($string)));

		if ($this->artifactExist($link)) return '<a href="'.strtolower($link).'">'.$word.'</a>';
		return '<a href="'.$link.'" class="external">'.$word.'</a>';
	}

	//title list grouped by $string(tag) (note: breaks flow of page, redeclaring '<p>' to keep flow)
	private function createSpaciousList($string) {
		global $artifacts;

		$string = $this->cleanString($string);
		$list = null;

		//check if is custom list by checking if there are (++) seperating text elements
		if (strpos($string, '++') == false) {
			for ($i = 0; $i < sizeof($artifacts); $i++) {
				if ($artifacts[$i]->hasTag($string) && !$artifacts[$i]->hasTag('nav')) $list = $list.'<li>'.$artifacts[$i]->attributes['title'].'</li>';
			}
		} else {
			$strings = explode('++', trim($string));
			for ($i = 0; $i < sizeof($strings); $i++) {
				$list = $list.'<li>'.trim($strings[$i]).'</li>';
			}
		}

		return '</p><ul class="spacious-list">'.$list.'</ul><p>';
	}

	//link list grouped by $string(tag) (note: breaks flow of page, redeclaring '<p>' to keep flow)
	private function createCondensedList($string) {
		global $artifacts;

		$string = $this->cleanString($string);
		$list = null;

		//check if is custom list by checking if there are commas seperating text elements
		if (strpos($string, '++') == false) {
			for ($i = 0; $i < sizeof($artifacts); $i++) {
				if ($artifacts[$i]->hasTag($string) && !$artifacts[$i]->hasTag('nav')) $list = $list.'<li><a href="'.$artifacts[$i]->attributes['name'].'">'.$artifacts[$i]->attributes['name'].'</a></li>';
			}
		} else {
			$strings = explode('++', trim($string));
			for ($i = 0; $i < sizeof($strings); $i++) {
				$list = $list.'<li>'.trim($strings[$i]).'</li>';
			}
		}

		return '</p><ul class="condensed-list">'.$list.'</ul><p>';
	}

	//monospaced note (note: breaks flow of page, redeclaring '<p>' to keep flow)
	private function createNote($string) {
		$string = $this->cleanString($string);
		$string = '</p><div class="note">'.$string.'</div><p>';
		return $string;
	}

	//indented quote (note: breaks flow of page, redeclaring '<p>' to keep flow)
	private function createQuote($string) {
		$string = $this->cleanString($string);
		$string = '</p><div class="quote">'.$string.'</div><p>';
		return $string;
	}

	//executes PHP code (use at your own risk)
	private function executePHP($string) {
		$string = $this->cleanString($string);
		$string = '$string = ' . $string;
		eval($string);

		return $string;
	}

	//image path
	private function createImagePath($string) {
		$strings = array();
		$strings = explode('>', trim($string));

		$image = $this->imageDirectory;

		for ($i = 0; $i < sizeof($strings); $i++) {
			$image = $image.'/'.$strings[$i];
		}

		$image = $image.'.png';
		if (!file_exists($image)) $image = substr($image, 0, strlen($image) - 4).'.jpg';
		if (!file_exists($image)) $image = substr($image, 0, strlen($image) - 4).'.gif';
		if (!file_exists($image)) $image = substr($image, 0, strlen($image) - 4).'.svg';

		$image = str_replace(' ', '%20', $image);
		$image = str_replace("'", "\'", $image);

		return $image;
	}

	//image with optional annotation (note: breaks flow of page, redeclaring '<p>' to keep flow)
	private function createImage($string) {
		$string = $this->cleanString($string);

		$annotationParts = explode('++', trim($string));
		$annotation = '';
		$newString = $string;

		if ($annotationParts[0] != $string) {
			$newString = $annotationParts[0];
			$annotation = '<span class="image-annotation">'. trim($annotationParts[1]) .'</span>';
		}
		
		$strings = array();
		$strings = explode('>', trim($newString));

		$image = $this->imageDirectory;
		
		for ($i = 0; $i < sizeof($strings); $i++) {
			$image = $image.'/'.$strings[$i];
		}

		$image = $image.'.png';
		if (!file_exists($image)) $image = substr($image, 0, strlen($image) - 4).'.jpg';
		if (!file_exists($image)) $image = substr($image, 0, strlen($image) - 4).'.gif';
		if (!file_exists($image)) $image = substr($image, 0, strlen($image) - 4).'.svg';

		$image = str_replace(' ', '%20', $image);

		if ($annotation != '') $img = '</p><img class="text-image-annotated" src="'.$image.'">' . $annotation . '<p>';
		else $img = '</p><img class="text-image" src="'.$image.'"><p>';

		return $img;
	}

	//audio player with optional annotation (note: breaks flow of page, redeclaring '<p>' to keep flow)
	private function createAudio($string) {
		$string = $this->cleanString($string);

		$annotationParts = explode('++', trim($string));
		$annotation = '';
		$newString = $string;

		if ($annotationParts[0] != $string) {
			$newString = $annotationParts[0];
			$annotation = '<span class="audio-annotation">'. trim($annotationParts[1]) .'</span>';
		}

		$strings = array();
		$strings = explode('>', trim($newString));

		$sound = $this->soundDirectory;

		for ($i = 0; $i < sizeof($strings); $i++) {
			$sound = $sound.'/'.$strings[$i];
		}

		$sound = $sound.'.mp3';
		if (!file_exists($sound)) $sound = substr($sound, 0, strlen($sound) - 4).'.wav';
		if (!file_exists($sound)) $sound = substr($sound, 0, strlen($sound) - 4).'.acc';
		if (!file_exists($sound)) $sound = substr($sound, 0, strlen($sound) - 4).'.ogg';

		$sound = str_replace(' ', '%20', $sound);
		
		if ($annotation != '') $aud = '</p>'.$annotation.'<audio controls class="audio-annotated"><source class="audio-source" src="'.$sound.'"></audio><p>';
		else $aud = '</p><audio controls class="audio"><source class="audio-source" src="'.$sound.'"></audio><p>';
		
		return $aud;
	}

	//video player with optional annotation (note: breaks flow of page, redeclaring '<p>' to keep flow)
	private function createVideo($string) {
		$string = $this->cleanString($string);

		$annotationParts = explode('++', trim($string));
		$annotation = '';
		$newString = $string;

		if ($annotationParts[0] != $string) {
			$newString = $annotationParts[0];
			$annotation = '<span class="video-annotation">'. trim($annotationParts[1]) .'</span>';
		}

		$strings = array();
		$strings = explode('>', trim($newString));

		$video = $this->videoDirectory;

		for ($i = 0; $i < sizeof($strings); $i++) {
			$video = $video.'/'.$strings[$i];
		}

		$video = $video.'.mp4';
		if (!file_exists($video)) $video = substr($video, 0, strlen($video) - 4).'.webm';
		if (!file_exists($video)) $video = substr($video, 0, strlen($video) - 5).'.ogg';

		$video = str_replace(' ', '%20', $video);
		
		if ($annotation != '') $vid = '</p><video controls class="video-annotated"><source class="video-source" src="'.$video.'"></video>'.$annotation.'<p>';
		else $vid = '</p><video controls class="video"><source class="video-source" src="'.$video.'"></video><p>';
		
		return $vid;
	}

	//subtitle (note: breaks flow of page, redeclaring '<p>' to keep flow)
	private function createSubtitle($string) {
		$string = $this->cleanString($string);
		$string = '</p><h1>'.$string.'</h1><p>';
		return $string;
	}

	//italic
	private function createItalic($string) {
		$string = $this->cleanString($string);
		$string = '<em>'.$string.'</em>';
		return $string;
	}

	//bold
	private function createBold($string) {
		$string = $this->cleanString($string);
		$string = '<strong>'.$string.'</strong>';
		return $string;
	}

	//creates reference to self, or to another artifact's data
	private function createReference($string) {
		global $v;
		global $artifacts;

		$string = $this->cleanString($string);

		if (strtolower($string) == 'self') return $v;
		else {
			$strings = array();
			$strings = explode('>', trim($string));

			for ($i = 0; $i < sizeof($artifacts); $i++) {
				if ($this->artifactExist($strings[0])) {
					$art = $this->getArtifact($strings[0]);
					return $art->attributes[$strings[1]];
				} 
			}
			return '<span>'.$strings[0].'.'.$strings[1].'</span>';
		}
	}

	//divider (note: breaks flow of page, redeclaring '<p>' to keep flow)
	private function createDivider($string) {
		$string = $this->cleanString($string);
		$string = '</p><div class="divider"></div><p>';
		return $string;
	}

	//cleans page tags
	private function cleanParagraphs($string) {
		//removes potential beginning closing paragraph tag if flow breaking element is first in string
		if (substr($string, 0, 4) === '</p>') $string = substr($string, 4);
		//add beginning paragraph open
		else $string = '<p>'. $string;

		//remove last opening paragraph tag if no text is present
		if (substr($string, -3, 3) === '<p>') $string = substr($string, 0, strlen($string) - 4);
		//add paragraph closer at end if paragraph tag not empty
		else $string = $string . '</p>';

		//unclosed tags - ignore warnings when using DOMDocument for parsing
		//add meta info to foce utf-8 encoding
		$doc = new DOMDocument();
		@$doc->loadHTML('<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $string);
		$string = $doc->saveHTML();

		//empty <p> tags
		$paragraphPattern = '/<p[^>]*>([\s]|&nbsp;)*<\/p>/';

		$string = preg_replace($paragraphPattern, '', $string);
		$string = trim($string);

		return $string;
	}

	//finds all instances of a substring($needle) in a string($haystack)
	private function allStringPositions($haystack, $needle) {
		$offset = 0;
		$all = array();

		while (($pos = strpos($haystack, $needle, $offset)) !== false) {
			$offset = $pos + 1;
			array_push($all, $pos);
		}
		return $all;
	}

	//removes symbol and [] (first two characters and last character) from $string
	private function cleanString($string) {
		$string = substr_replace($string, '', -1);
		$string = substr_replace($string, '', 0, 2);
		return $string;
	}

	//check if artifact exists
	private function artifactExist($string) {
		global $artifacts;

		for ($i = 0; $i < sizeof($artifacts); $i++) {
			if (strtolower($artifacts[$i]->attributes['name']) === strtolower($string)) return true;
		}
		return false;
	}

	//find artifact
	private function getArtifact($string) {
		global $artifacts;

		for ($i = 0; $i < sizeof($artifacts); $i++) {
			if (strtolower($artifacts[$i]->attributes['name']) === strtolower($string)) return $artifacts[$i];
		}
		return null;
	}
}
?>