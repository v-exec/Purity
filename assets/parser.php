<?php
/*
Parser holds a variety of functions made to parse the various attributes of an artifact (typically into the corresponding html).
It features a series of generalized formatting functions.

In the event of an expansion or customization of this system,
new parsing criteria and features can seamlessly be introduced as a basic addition to the existing code.

The 'github' and 'path' attributes are the only that feature a specialized formatting, made specifically for V-OS.
*/
class Parser {
	//all acceptable tags, and what they should read out when creating path
	private $tags = array(
		['project', 'projects'],
		['verse', 'verse'],
		['personal', 'personal'],
		['audio', 'audio'],
		['visual', 'visual'],
		['code', 'code'],
		['tool', 'tools'],
		['interactive', 'interactive'],
		['display', 'display'],
		['graphic', 'graphic'],
		['photography', 'photography'],
		['single', 'singles'],
		['album', 'albums'],
		['people', 'people'],
		['person', 'people'],
		['location', 'locations'],
		['system', 'systems'],
		['secret', 'secret']
	);

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

		//create path
		if ($artifact->tags) $this->createPath($artifact);

		//create image
		if($artifact->attributes['image']) $artifact->attributes['image'] = $this->createImage($artifact->attributes['image'], "", false);

		//make github into link
		if ($artifact->attributes['github']) $artifact->attributes['github'] = '<a href="'.$artifact->attributes['github'].'" class="header-link neutral-link">github</a>';

		//format image name
		if ($artifact->attributes['image name']) {
			$this->formatText($artifact, 'image name', '#', 'class="header-title neutral-link"');
			$this->formatText($artifact, 'image name', '@', 'class="header-title neutral-link"');
		}

		//format title
		if ($artifact->attributes['title']) {
			$this->formatText($artifact, 'title', '#', '');
			$this->formatText($artifact, 'title', '_', '');
			$this->formatText($artifact, 'title', '*', '');
			$this->formatText($artifact, 'title', '$', '');
			$this->formatText($artifact, 'title', '@', '');
		}

		//format content
		if ($artifact->attributes['content']) {
			$this->formatText($artifact, 'content', '#', '');
			$this->formatText($artifact, 'content', '_', '');
			$this->formatText($artifact, 'content', '*', '');
			$this->formatText($artifact, 'content', '@', '');
			$this->formatText($artifact, 'content', '~', '');
			$this->formatText($artifact, 'content', '?', '');
			$this->formatText($artifact, 'content', '&', 'class="text-image"');
			$this->formatText($artifact, 'content', '%', 'class="small-divider"');
			$this->formatText($artifact, 'content', '!', '');
		}
	}

	//goes through remaining artifact attributes (ones that are dependant on the previous formats being complete) and formats each one according to the existing formatting rules
	public function secondFormat($artifact) {
		//format content
		if ($artifact->attributes['content']) {
			$this->formatText($artifact, 'content', '-', '');
			$this->formatText($artifact, 'content', '=', '');
			$this->formatText($artifact, 'content', '$', 'class="reference"');

			//removes empty paragraph tags from body
			$paragraphPattern = '/<p[^>]*>([\s]|&nbsp;)*<\/p>/';
			$artifact->attributes['content'] = preg_replace($paragraphPattern, '', $artifact->attributes['content']);

			//removes potential beginning closing paragraph tag if flow breaking element is first in 'content'
			if (substr($artifact->attributes['content'], 0, 4) === '</p>') $artifact->attributes['content'] = substr($artifact->attributes['content'], 4);
			//add beginning paragraph open
			else $artifact->attributes['content'] = '<p>'. $artifact->attributes['content'];

			//remove last opening paragraph tag if no text is present
			if (substr($artifact->attributes['content'], -3, 3) === '<p>') $artifact->attributes['content'] = substr($artifact->attributes['content'], 0, sizeof($artifact->attributes['content']) - 4);
			//add paragraph closer at end if paragraph tag not empty
			else $artifact->attributes['content'] = $artifact->attributes['content'] . '</p>';
		}
	}

	//creates a path for predetermined hierarchy/navigation, based on tags
	private function createPath($artifact) {
		//set up artifact path
		$artifact->attributes['path'] = '<a href="site" class="path neutral-link">site</a><span class="path">/</span>';

		//check all artifact tags for parse tags
		for ($i = 0; $i < sizeof($this->tags); $i++) {
			if ($artifact->hasTag($this->tags[$i][0])) $artifact->attributes['path'] = $artifact->attributes['path'].'<a href="'.$this->tags[$i][1].'" class="path neutral-link">'.$this->tags[$i][1].'</a><span class="path">/</span>';

			//hub tag clears path
			if ($artifact->hasTag('hub')) $artifact->attributes['path'] = null;
		}
		//assign path
		$artifact->attributes['path'] = $artifact->attributes['path'].'<a href="'.strtolower($artifact->attributes['name']).'" class="path neutral-link">'.strtolower($artifact->attributes['name']).'</a>';
	}

	//finds all instances of $symbol[] within $artifact->attributes[$attribute], and replaces it with the appropriate html element, and applies custom $style to said element
	//manages nested brackets
	private function formatText($artifact, $attribute, $symbol, $style) {
		//if number of opening brackets and closing brackets is uneven count, display error (specific to V-OS)
		if (sizeof($this->allStringPositions($artifact->attributes[$attribute], '[')) != sizeof($this->allStringPositions($artifact->attributes[$attribute], ']'))) {
			$artifact->attributes['image'] = null;
			$artifact->attributes['image name'] = null;
			$artifact->attributes['github'] = null;
			$artifact->attributes['content'] = null;
			$artifact->attributes['white'] = null;
			$artifact->attributes['path'] = null;
			$artifact->tags = null;
			$artifact->attributes['title'] = 'There was an error loading this page. Please contact <a href="LOGO">LOGO</a>.';
			return;
		}

		//get all instances of '$symbol['
		$positions = $this->allStringPositions($artifact->attributes[$attribute], $symbol.'[');

		if (isset($positions[0])) {

			while (sizeof($positions) > 0) {
				//find closing ']'
				$end = strpos($artifact->attributes[$attribute], ']', $positions[0]);

				//check if any other '[]' pairs exist within substring, suggesting we haven't found the proper ']'
				//find next ']' until we've found the proper ']'
				while (sizeof($this->allStringPositions(substr($artifact->attributes[$attribute], $positions[0], $end - $positions[0] + 1), '[')) != sizeof($this->allStringPositions(substr($artifact->attributes[$attribute], $positions[0], $end - $positions[0] + 1), ']'))) {
					$end = strpos($artifact->attributes[$attribute], ']', $end + 1);
				}

				//depending on $symbol, run proper format rule
				$string = substr($artifact->attributes[$attribute], $positions[0], $end - $positions[0] + 1);
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

					default:
						return;
						break;
				}
				//replace attribute with formatted attribute
				$artifact->attributes[$attribute] = str_replace($string, $new, $artifact->attributes[$attribute]);
				//find next '$symbol[' to parse
				$positions = $this->allStringPositions($artifact->attributes[$attribute], $symbol.'[');
			}
		}
	}

	//takes $string and makes it into link with custom $style
	private function createLink($string, $style) {
		global $artifacts;

		$string = $this->cleanString($string);
		if ($this->artifactExist($string)) return '<a href="'.strtolower($string).'" '.$style.'>'.$string.'</a>';
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

		if ($this->artifactExist($link)) return '<a href="'.strtolower($link).'" '.$style.'>'.$word.'</a>';
		return '<a href="'.$link.'" class="external">'.$word.'</a>';
	}

	//takes $string and creates title list grouped by $string(tag) (note: breaks flow of page, redeclaring '<p>' to keep flow)
	private function createSpaciousList($string) {
		global $artifacts;

		$string = $this->cleanString($string);
		$list = null;

		//check if is custom list by checking if there are commas seperating text elements
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

	//takes $string and makes it into image with custom $style, only returns image path if $returnImg == false
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

		$image = str_replace(' ', '%20', $image);

		if ($returnImg) {
			$img = '<img '.$style.' src="'.$image.'">';
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

		if ($string == 'self') return $v;
		else {
			$strings = array();
			$strings = explode('>', trim($string));

			for ($i = 0; $i < sizeof($artifacts); $i++) {
				if ($this->artifactExist($strings[0])) {
					$art = getArtifact($strings[0]);
					return '<span '.$style.'>'.$art->attributes[$strings[1]].'</span>';	
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