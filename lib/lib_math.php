<?php
/**
 * Calcola una stringa matematica come "10 - 1" e restituisci il valore
 */
function calculate_string( $mathString )    {
	//converti numeri in lettere
	$mathString = str_parse_num($mathString);
    //rimuovi spazi
	$mathString = trim($mathString);     									
	//rimuovi lettere rimanenti
    $mathString = preg_replace('/[^0-9\+\-\*\/\(\) ]/', '', $mathString);

 
    // Gestione eccezioni
	try {
		$compute = create_function("", "return (" . $mathString . ");" );
	} catch (Exception $e) {
		echo 'Caught exception: ',  $e->getMessage(), "\n";
	}
	
    return 0 + $compute();
}

/**
 * Parsa una stringa come "dieci - 1" e convertila in "10 - 1"
 */
function str_parse_num($mathString){
	return preg_replace_callback(		"/(?:\s)?([a-zA-Z]+)(?:\s)?/", 
									function ($matches) { 
									$num = wordsToNumber($matches[1]);
										if($num!=0) return $num; else return $matches[0];
									}, $mathString);
}

/**
 * Convert a string such as "one hundred thousand" to 100000.00.
 *
 * @param string $data The numeric string.
 *
 * @return float or false on error
 */
 // echo wordsToNumber("venti"); echo "<br>";
 // echo wordsToNumber("centoundici"); echo "<br>";
 // echo wordsToNumber("settecento"); echo "<br>";
 // echo wordsToNumber("mille e settecento"); echo "<br>"; 
 // echo wordsToNumber("duemila e cinquecento"); echo "<br>"; 
 // echo wordsToNumber("novemila e novecentonovantanove"); echo "<br>"; 
 // echo wordsToNumber("centomila e cinquecento"); echo "<br>"; 
 // echo wordsToNumber("duemilioni e cinquecento"); echo "<br>"; 
 // echo wordsToNumber("ottomiliardi e novecentomilioni e cinquecentomila"); echo "<br>"; 
function wordsToNumber($data) {
    // Replace all number words with an equivalent numeric value
    $data = strtr(
        $data,
        array(
            'zero'      => '0',
            'un'         => '1 e ',
            'uno'       => '1 e ',
            'due'       => '2 e ',
            'tre'     => '3 e ',
            'quattro'      => '4 e ',
            'cinque'      => '5 e ',
            'sei'       => '6 e ',
            'sette'     => '7 e ',
            'otto'     => '8 e ',
            'nove'      => '9 e ',
            'dieci'       => '10 e ',
            'undici'    => '11 e ',
            'dodici'    => '12 e ',
            'tredici'  => '13 e ',
            'quattordici'  => '14 e ',
            'quindici'   => '15 e ',
            'sedici'   => '16 e ',
            'diciassette' => '17 e ',
            'diciotto'  => '18 e ',
            'diciannove'  => '19 e ',
            'venti'    => '20 e ',
            'vent'    => '20 e ',
            'trenta'    => '30 e ',
            'trent'    => '30 e ',
            'quaranta'     => '40 e ',
            'quarant'    => '40 e ',
            'cinquanta'     => '50 e ',
            'cinquant'     => '50 e ',
            'sessanta'     => '60 e ',
            'sessant'     => '60 e ',
            'settanta'   => '70 e ',
            'settant'   => '70 e ',
            'ottanta'    => '80 e ',
            'ottant'    => '80 e ',
            'novanta'    => '90 e ',
            'novant'    => '90 e ',
            'cento'   => '100 e ',
            'mille'  => '1000',
            'mila'  => '1000',
            'milione'   => '1000000',
            'milioni'   => '1000000',
            'miliardo'   => '1000000000',
            'miliardi'   => '1000000000',
            'e'       => '',
        )
    );
	
    // Coerce all tokens to numbers
    $parts = array_map(
        function ($val) {
            return floatval($val);
        },
		preg_split('/[\s-]+/', $data)
    );
	//var_dump($parts);

    $stack = new SplStack; // Current work stack
    $sum   = 0; // Running total
    $last  = null;

    foreach ($parts as $part) {
        if (!$stack->isEmpty()) {
            // We're part way through a phrase
            if ($stack->top() > $part) {
                // Decreasing step, e.g. from hundreds to ones
                if ($last >= 1000) {
                    // If we drop from more than 1000 then we've finished the phrase
                    $sum += $stack->pop();
                    // This is the first element of a new phrase
                    $stack->push($part);
                } else {
                    // Drop down from less than 1000, just addition
                    // e.g. "seventy one" -> "70 1" -> "70 + 1"
                    $stack->push($stack->pop() + $part);
                }
            } else {
                // Increasing step, e.g ones to hundreds
                $stack->push($stack->pop() * $part);
            }
        } else {
            // This is the first element of a new phrase
            $stack->push($part);
        }

        // Store the last processed part
        $last = $part;
    }

    return $sum + $stack->pop();
}



/**
 * Converti un numero o una stringa come "10,1" in "dieci virgola uno"
 *
 * "10.1" => "dieci virgola uno"
 * "10,1" => "dieci virgola uno"
 *  10,1  => "dieci virgola uno"
 *
 * @param string $data The numeric string.
 */
function numbertoWords($number) {
    
    $hyphen      = ' ';
    $conjunction = '';
    $separator   = ' e ';
    $negative    = 'meno ';
    $decimal     = ' virgola ';
    $dictionary  = array(
        0                   => 'zero',
        1                   => 'uno',
        2                   => 'due',
        3                   => 'tre',
        4                   => 'quattro',
        5                   => 'cinque',
        6                   => 'sei',
        7                   => 'sette',
        8                   => 'otto',
        9                   => 'nove',
        10                  => 'dieci',
        11                  => 'undici',
        12                  => 'dodici',
        13                  => 'tredici',
        14                  => 'quattordici',
        15                  => 'quindici',
        16                  => 'sedici',
        17                  => 'diciasette',
        18                  => 'diciotto',
        19                  => 'diciannove',
        20                  => 'venti',
        30                  => 'trenta',
        40                  => 'quaranta',
        50                  => 'cinquanta',
        60                  => 'sessanta',
        70                  => 'settanta',
        80                  => 'ottanta',
        90                  => 'novanta',
        100                 => 'cento',
        1000                => 'mille',
		"@1000"                => 'mila',
        1000000             => 'un milione',
        1000000000          => 'un miliardo',
//        1000000000000       => 'un trilione',
//        1000000000000000    => 'quadrillion',
//        1000000000000000000 => 'quintillion'
    );
    
	if (!isset($number)){
		return false;
	}
	
    if (is_string($number)) {
		//cambia , con .
		if (strpos($number, ',') !== false) {
			$number = str_replace(",",".",$number);
		}
		//togli spazi
		$number = str_replace(" ","",$number);
		//togli segno "+"
		if (strpos($number, '+') !== false) {
			$number = str_replace("+","",$number);
		}
    }
    
    if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
        // overflow
        trigger_error(
            'numbertoWords only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
            E_USER_WARNING
        );
        return false;
    }

    if ($number < 0) {
		$numofdec = strlen(substr(strrchr($number, "."), 1));;
        return $negative . numbertoWords(number_format(abs($number),$numofdec));
    }
    
    $string = $fraction = null;
    
    if (strpos($number, '.') !== false) {
        list($number, $fraction) = explode('.', $number);
    }
    
    switch (true) {
        case $number < 21:
			//remove 0 initials
			$string = ""; //echo $number . "-" . substr($number,1); exit;
			while(substr($number,0,1)=="0" && strlen($number)>1){
				$string .= $dictionary[0]." ";
				$number = substr($number,1);
			}
			$string .= $dictionary[(string)$number];
            break;
        case $number < 100:
            $tens   = ((int) ($number / 10)) * 10;
            $units  = $number % 10;
            $string = $dictionary[$tens];
            if ($units) {
                //$string .= $hyphen . $dictionary[$units];
				$string = wordcollision ($string , $dictionary[$units]);
            }
            break;
        case $number < 1000:
            $hundreds  = $number / 100;
            $remainder = $number % 100; 
			if (intval($hundreds)==1) $string = $dictionary[100]; else
            $string = $dictionary[$hundreds] . '' . $dictionary[100];
            if ($remainder) {
                $string .= $conjunction . numbertoWords($remainder);
            }
            break;
        default:
            $baseUnit = pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
			if($numBaseUnits == 1 && $baseUnit == 1000){
				$string = 'mille';
			} else 
				$string = numbertoWords($numBaseUnits) . '' . $dictionary['@'.$baseUnit];
            if ($remainder) {
                //$string .= $remainder < 100 ? $conjunction : $separator;
                $string .= $separator . numbertoWords($remainder);
            }
            break;
    }
    
    if (null !== $fraction && is_numeric($fraction)) {
        $string .= $decimal . numbertoWords($fraction);
		/*
		$string .= $decimal;
        $words = array();
        foreach (str_split((string) $fraction) as $number) {
            $words[] = $dictionary[$number];
        }
        $string .= implode(' ', $words);
		*/
    }
    
    return $string;
}

function wordcollision($wrd1,$wrd2){
	$vocals = array("a", "e", "i", "o", "u");
	if  ( 	in_array(substr($wrd1, -1), $vocals) && 
			in_array(substr($wrd2,  0, 1), $vocals)
		) {
		return substr($wrd1, 0 , -1) . $wrd2 ;
	} else {
		return $wrd1 . $wrd2 ;
	}
		
	
}


/*
echo numbertoWords("-0.11"); exit;
echo numbertoWords(0)."<br>";
for($i=0;$i<6;$i=$i+0.1){
	echo $i . " => " . numbertoWords($i)."<br>";
}
*/

?>