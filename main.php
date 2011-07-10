<?php
$xmlstr = file_get_contents('a');
function parse_text($src) {
	$xmlstr = str_replace('=3D', '=', $src);
	$doc = new DOMDocument();
	@$doc->loadHTML($xmlstr);
	$xpath = new DOMXPath($doc);
	$query = '//html/body/table/tr/td[@class="xl34"]';
	$query .= '|//html/body/table/tr/td[@class="xl35"]';
	$query .= '|//html/body/table/tr/td[@class="xl36"]';
	$query .= '|//html/body/table/tr/td[@class="xl37"]';
	$query .= '|//html/body/table/tr/td[@class="xl45"]';
	$query .= '|//html/body/table/tr/td[@class="xl46"]';
	$query .= '|//html/body/table/tr/td[@class="xl47"]';
	$query .= '|//html/body/table/tr/td[@class="xl48"]';
	$entries = $xpath->query($query);

	$foods = array();
	$prices = array();
	foreach ($entries as $entry) {
		//var_dump($entry);
		//print_r($entry->attributes->getNamedItem('class')->nodeValue);
		if (empty($entry->nodeValue)) continue;
		$c = count($foods) + count($prices);
		if (strpos($entry->nodeValue, '....') !== FALSE) {
			$food = rtrim(preg_replace('/\s+/', ' ', $entry->nodeValue), '.');
			if (!empty($food)) $foods[] = trim($food);
		}
		else {
			$price = strip_tags($entry->nodeValue);
			if (strpos($price, '=A0') !== FALSE && strpos($price, '$') !== FALSE && strpos($price, ',') !== FALSE) {
				$price = str_replace('=A0', '', $price);
				$price = str_replace('$', '', $price);
				$price = str_replace(',', '.', $price);
				$prices[] = (float)$price;
			}
		}
		if ($c == count($foods) + count($prices)) {
			var_dump($entry->nodeValue);
		}
	}

	/*
	/** /
	for ($i=0;$i<max(count($prices),count($foods));$i++)
		echo @$foods[$i] , ' ' , @$prices[$i] , "\n";
	/**/

	if (count($prices) == count($foods)) {
		if (count($prices) == 0) return TRUE;
		$matrix = array_combine($foods, $prices);
		return $matrix;
	} else {
		echo 'count($prices) = ' . count($prices) , "\n";
		echo 'count($foods) = ' . count($foods) , "\n";
		file_put_contents('bad', $src);
		return FALSE;
	}
}

//parse_text(file_get_contents('bad'));die;

function prompt_silent($prompt = "Enter Password:") {
	if (preg_match('/^win/i', PHP_OS)) {
		$vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
		file_put_contents(
			$vbscript, 'wscript.echo(InputBox("'
			. addslashes($prompt)
			. '", "", "password here"))');
		$command = "cscript //nologo " . escapeshellarg($vbscript);
		$password = rtrim(shell_exec($command));
		unlink($vbscript);
		return $password;
	} else {
		$command = "/usr/bin/env bash -c 'echo OK'";
		if (rtrim(shell_exec($command)) !== 'OK') {
			trigger_error("Can't invoke bash");
			return;
		}
		$command = "/usr/bin/env bash -c 'read -s -p \""
			. addslashes($prompt)
			. "\" mypassword && echo \$mypassword'";
		$password = rtrim(shell_exec($command));
		echo "\n";
		return $password;
	}
}

$server = '{imap.gmail.com:993/imap/ssl}';
$imap = imap_open($server . 'INBOX/El Cervatillo', 'seppo0010@gmail.com', prompt_silent()) or die("can't connect: " . print_r(imap_errors()));
$check = imap_check($imap) or die('Unable to check \'El Cervatillo\'');
$nmsgs = $check->Nmsgs;
var_dump($nmsgs);
$result = imap_fetch_overview($imap,'1:' . $nmsgs,0);
foreach ($result as $overview) {
	$time = strtotime($overview->date);
	if (is_file($time . '.json')) {
		continue;
	} else if (!is_file($time . '.email')) {
		$body = imap_fetchbody ($imap, $overview->msgno, '2', FT_PEEK);
		if ($body) file_put_contents($time . '.email', $body);
	} else {
		$body = file_get_contents($time . '.email');
	}
	$matrix = parse_text($body);
	if ($matrix === FALSE) break;
	file_put_contents($time . '.json', json_encode($matrix));
}
imap_close($imap);

//var_dump(prompt_silent());
