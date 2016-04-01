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

?>