# TextCat

     /\_/\
    ( . . )
    =\_v_/=

This is a PHP port of the TextCat language guesser utility.

Please see http://odur.let.rug.nl/~vannoord/TextCat/ for the original one.

## Contents

The package contains the classifier class itself and two tools - for classifying the texts and for generating the ngram database. 
The code now assumes the text encoding is UTF-8, since it's easier to extract ngrams this way.
Also, everybody uses UTF-8 now and I, for one, welcome our new UTF-8-encoded overlords.

### Classifier

Classifier is the script `catus.php` can be run as:

    echo "Bonjour tout le monde, ceci est un texte en français" | php catus.php -d LM

or 

    php catus.php -d LM -l "Bonjour tout le monde, ceci est un texte en français" 

The output would be the list of the languages, e.g.:

    french or romanian

### Generator

To generate the database from set of texts, use the script `felis.php`. It can be run as:

    php felis.php INPUTDIR OUTPUTDIR

And will read texts from `INPUTDIR` and generate ngams files in `OUTPUTDIR`. 
The files in `INPUTDIR` are assumed to have names like `LANGUAGE.txt`, e.g. `english.txt`, `german.txt`, `klingon.txt`, etc. 
