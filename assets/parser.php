<?php
/*
Parser holds a variety of functions made to parse the various attributes of an artifact (typically into the corresponding html).
It features a series of generalized formatting functions.

In the event of an expansion or customization of this system,
new parsing criteria and features can seamlessly be introduced as a basic addition to the existing code.
*/
class Parser {
	//image directory
	private $imageDirectory = 'images';

	//goes through all artifact attributes that are independant from other artifacts and formats each one according to existing formatting rules
	public function firstFormat($artifact) {
		//trim tags
		if ($artifact->tags) {
			for ($i = 0; $i < sizeof($artifact->tags); $i++) {
				$artifact->tags[$i] = trim($artifact->tags[$i]);
			}
		}

		//create image
		if($artifact->attributes['image']) $artifact->attributes['image'] = $this->createImage($artifact->attributes['image'], "", false);

		//format image name
		if ($artifact->attributes['image name']) {
			$this->formatText($artifact, 'image name', '#', 'class="neutral-link"');
			$this->formatText($artifact, 'image name', '@', 'class="neutral-link"');
		}

		//format title
		if ($artifact->attributes['title']) {
			$this->formatText($artifact, 'title', '@', '');
			$this->formatText($artifact, 'title', '#', '');
			$this->formatText($artifact, 'title', '_', '');
			$this->formatText($artifact, 'title', '*', '');
			$this->formatText($artifact, 'title', '$', '');
			$this->formatText($artifact, 'title', '>', '');
		}

		//format content
		if ($artifact->attributes['content']) {
			$this->formatText($artifact, 'content', '@', '');
			$this->formatText($artifact, 'content', '#', '');
			$this->formatText($artifact, 'content', '_', '');
			$this->formatText($artifact, 'content', '*', '');
			$this->formatText($artifact, 'content', '~', '');
			$this->formatText($artifact, 'content', '?', '');
			$this->formatText($artifact, 'content', '&', 'class="text-image"');
			$this->formatText($artifact, 'content', '%', 'class="small-divider"');
			$this->formatText($artifact, 'content', '!', '');
			$this->formatText($artifact, 'content', '>', '');
		}
	}

	//goes through remaining artifact attributes (ones that are dependant on the previous formats being complete) and formats each one according to the existing formatting rules
	public function secondFormat($artifact) {
		//format content
		if ($artifact->attributes['content']) {

			//reformat 10 times to allow for 10 levels of nested artifact content retrieval
			//could be theoretically infinite, but that doesn't seem practical, and will easily end up with infinite loops
			for ($i = 0; $i < 10; $i++) {
				$this->formatText($artifact, 'content', '-', '');
				$this->formatText($artifact, 'content', '=', '');
				$this->formatText($artifact, 'content', '$', '');
			}
			
			//clean paragraphs
			$artifact->attributes['content'] = $this->cleanParagraphs($artifact->attributes['content']);
		}
	}

	//finds all instances of $symbol[] within $artifact->attributes[$attribute], and replaces it with the appropriate html element, and applies custom $style to said element
	//manages nested brackets
	private function formatText($artifact, $attribute, $symbol, $style) {
		//check open vs closed brackets by using counter to match corresponding brackets
		//if number of opening brackets and closing brackets is uneven count, display error
		if (sizeof($this->allStringPositions($artifact->attributes[$attribute], '[')) != sizeof($this->allStringPositions($artifact->attributes[$attribute], ']'))) {
			$artifact->attributes['image'] = null;
			$artifact->attributes['image name'] = null;
			$artifact->attributes['content'] = null;
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
					$new = $this->createSubtitle($string, $style);
					break;
				
				case '&':
					$new = $this->createImage($string, $style, true);
					break;

				case '#':
					$new = $this->createLink($string, $style);
					break;

				case '*':
					$new = $this->createBold($string);
					break;

				case '_':
					$new = $this->createItalic($string);
					break;

				case '%':
					$new = $this->createDivider($string, $style);
					break;

				case '$':
					$new = $this->createReference($string, $style);
					break;

				case '@':
					$new = $this->createCustomLink($string, $style);
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

	//takes $string and makes it into link with custom $style
	private function createLink($string, $style) {
		global $artifacts;

		$string = $this->cleanString($string);
		if ($this->artifactExist($string)) return '<a href="'.strtolower($string).'"'.$style.'>'.$string.'</a>';
		return '<span '.$style.'>'.$string.'</span>';
	}

	//takes $string and makes it into custom link with custom $style
	private function createCustomLink($string, $style) {
		global $artifacts;

		$string = $this->cleanString($string);
		$accessor = strpos($string, '>');

		//if accessor not found, return empty
		if ($accessor == false) return '';

		$word = trim(substr($string, 0, $accessor));
		$link = trim(substr($string, $accessor + 1, strlen($string)));

		if ($this->artifactExist($link)) return '<a href="'.strtolower($link).'"'.$style.'>'.$word.'</a>';
		return '<a href="'.$link.'" class="external">'.$word.'</a>';
	}

	//takes $string and creates title list grouped by $string(tag) (note: breaks flow of page, redeclaring '<p>' to keep flow)
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

	//takes $string and creates link list grouped by $string(tag) (note: breaks flow of page, redeclaring '<p>' to keep flow)
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

	//takes $string and makes it into monospaced note (note: breaks flow of page, redeclaring '<p>' to keep flow)
	private function createNote($string) {
		$string = $this->cleanString($string);
		$string = '</p><div class="note">'.$string.'</div><p>';
		return $string;
	}

	//takes $string and makes it into indented quote (note: breaks flow of page, redeclaring '<p>' to keep flow)
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

	//takes $string and makes it into image with custom $style, only returns image path if $returnImg == false (note: breaks flow of page, redeclaring '<p>' to keep flow)
	private function createImage($string, $style, $returnImg) {
		if ($returnImg) $string = $this->cleanString($string);

		$strings = array();
		$strings = explode('>', trim($string));

		$image = $this->imageDirectory;

		for ($i = 0; $i < sizeof($strings); $i++) {
			$image = $image.'/'.$strings[$i];
		}

		$image = $image.'.png';
		if (!file_exists($image)) $image = substr($image, 0, strlen($image) - 4).'.jpg';
		if (!file_exists($image)) $image = substr($image, 0, strlen($image) - 4).'.gif';

		$image = str_replace(' ', '%20', $image);

		if ($returnImg) {
			$img = '</p><img '.$style.' src="'.$image.'"><p>';
			return $img;
		} else return $image;
	}

	//takes $string and makes it into subtitle with custom $style (note: breaks flow of page, redeclaring '<p>' to keep flow)
	private function createSubtitle($string, $style) {
		$string = $this->cleanString($string);
		$string = '</p><h1 '.$style.'>'.$string.'</h1><p>';
		return $string;
	}

	//takes $string and makes it italic
	private function createItalic($string) {
		$string = $this->cleanString($string);
		$string = '<em>'.$string.'</em>';
		return $string;
	}

	//takes $string and makes it bold
	private function createBold($string) {
		$string = $this->cleanString($string);
		$string = '<strong>'.$string.'</strong>';
		return $string;
	}

	//creates reference to self, or to another artifact's data
	private function createReference($string, $style) {
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

	//makes divider with custom $style (note: breaks flow of page, redeclaring '<p>' to keep flow)
	private function createDivider($string, $style) {
		$string = $this->cleanString($string);
		$string = '</p><div '.$style.'></div><p>';
		return $string;
	}

	//gets rid of empty <p> tags
	private function cleanParagraphs($string) {
		$paragraphPattern = '/<p[^>]*>([\s]|&nbsp;)*<\/p>/';
		$string = preg_replace($paragraphPattern, '', $string);

		$string = trim($string);

		//removes potential beginning closing paragraph tag if flow breaking element is first in string
		if (substr($string, 0, 4) === '</p>') $string = substr($string, 4);
		//add beginning paragraph open
		else $string = '<p>'. $string;

		//remove last opening paragraph tag if no text is present
		if (substr($string, -3, 3) === '<p>') $string = substr($string, 0, sizeof($string) - 4);
		//add paragraph closer at end if paragraph tag not empty
		else $string = $string . '</p>';

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