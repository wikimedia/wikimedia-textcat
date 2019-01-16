# TextCat

     /\_/\
    ( . . )
    =\_v_/=
           PHP

This is a PHP port of the TextCat language guesser utility.

Please see also the [original Perl
version](http://odur.let.rug.nl/~vannoord/TextCat/), and an [updated
Perl version](https://github.com/Trey314159/TextCat).

## Contents

The package contains the classifier class itself and some tools—for
classifying the texts and for generating the ngram database. The code
now assumes the text encoding is UTF-8, since it's easier to extract
ngrams this way. Also, (almost) everybody uses UTF-8 now and I, for one,
welcome our new UTF-8–encoded overlords.

### Building the Package

Once you download the package, you need to build it with [`composer`](https://getcomposer.org/). Run the following command to install all the development-related dependencies:

    composer install

To install the minimum to get up and running, run the command with `--no-dev`.

    composer install --no-dev

Composer dependencies are installed in the `vendor/` directory and are necessary for the proper functioning of TextCat.

### Classifier

The classifier is the script `catus.php`, which can be run as:

    echo "Bonjour tout le monde, ceci est un texte en français" | php catus.php -d LM

or

    php catus.php -d LM -l "Bonjour tout le monde, ceci est un texte en français"

The output would be the list of detected languages, separated by `OR`,
e.g.:

    fr OR ro

Please note that the provided collection of language models includes a
model for [Oriya](https://en.wikipedia.org/wiki/Odia_language) (ଓଡ଼ିଆ),
which has the language code `or`, so results like `or OR sco OR ro OR nl`
are possible.

### Generator

To generate the language model database from a set of texts, use the
script `felis.php`. It can be run as:

    php felis.php INPUTDIR OUTPUTDIR

And will read texts from `INPUTDIR` and generate ngrams files in
`OUTPUTDIR`. The files in `INPUTDIR` are assumed to have names like
`LANGUAGE.txt`, e.g. `english.txt`, `german.txt`, `klingon.txt`, etc.

If you are working with sizable corpora (e.g., millions of characters),
you should set `$minFreq` in `TextCat.php` to a reasonably small value,
like `10`, to trim the very long tail of infrequent ngrams before they
are sorted. This reduces the CPU and memory requirements for generating
the language models. When *evaluating* texts, `$minFreq` should be set
back to `0` unless your input texts are fairly large.

### Converter

An additional script, `lm2php.php`, is provided to convert models in the
format used by the Perl versions of TextCat into the format used by this
version. It can be run as:

    php lm2php.php INPUTDIR OUTPUTDIR

Perl-style models in `INPUTDIR` will be converted to PHP-style models and
written to `OUTPUTDIR`, with the same name.

## Models

The package comes with a default language model database in the `LM`
directory and a query-based language model database in the `LM-query`
directory. However, model performance will depend a lot on the text
corpus it will be applied to, as well as specific modifications—e.g.
capitalization, diacritics, etc. Currently the library does not modify
or normalize either training texts or classified texts in any way, so
usage of custom language models may be more appropriate for specific
applications.

Model names use [Wikipedia language
codes](https://en.wikipedia.org/wiki/List_of_Wikipedias), which are
often but not guaranteed to be the same as [ISO 639 language
codes](https://en.wikipedia.org/wiki/ISO_639). (But see also
**Wrong-Keyboard/Encoding Models** below.)

When detecting languages, you will generally get better results when you
can limit the number of language models in use, especially with very
short texts. For example, if there is virtually no chance that your text
could be in Irish Gaelic, including the Irish Gaelic language model
(`ga`) only increases the likelihood of mis-identification. This is
particularly true for closely related languages (e.g., the Romance
languages, or English/`en` and Scots/`sco`).

Limiting the number of language models used also generally improves
performance. You can copy your desired language models into a new
directory (and use `-d` with `catus.php`) or specify your desired
languages on the command line (use `-c` with `catus.php`).

You can also combine models in multiple directories (e.g., to use the
query-based models with a fallback to Wiki-Text-based models) with a
comma-separated list of directories (use `-d` with `catus.php`).
Directories are scanned in order, and only the first model found with a
particular name will be used.

### Wiki-Text Models

The 70+ language models in `LM` are based on text extracted from randomly
chosen articles from the Wikipedia for that language. The languages
included were chosen based on a number of criteria, including the number
of native speakers of the language, the number of queries to the various
wiki projects in the language (not just Wikipedia), the list of
languages supported by the original TextCat, and the size of Wikipedia
in the language (i.e., the size of the collection from which to draw a
training corpus).

The training corpus for each language was originally made up of ~2.7 to
~2.8M million characters, excluding markup. The texts were then lightly
preprocessed. Preprocessing steps taken include: HTML Tags were removed.
Lines were sorted and `uniq`-ed (so that Wikipedia idiosyncrasies—like
"References", "See Also", and "This article is a stub"—are not
over-represented, and so that articles randomly selected more than once
were reduced to one copy). For corpora in Latin character sets, lines
containing no Latin characters were removed. For corpora in non-Latin
character sets, lines containing only Latin characters, numbers, and
punctuation were removed. This character-set-based filtering removed
from dozens to thousands of lines from the various corpora. For corpora
in multiple character sets (e.g., Serbo-Croatian/`sh`, Serbian/`sr`,
Turkmen/`tk`), no such character-set-based filtering was done. The final
size of the training corpora ranged from ~1.8M to ~2.8M characters.

These models have not been thoroughly tested and are provided as-is. We
may add new models or remove poorly-performing models in the future.

These models have 10,000 ngrams. The best number of ngrams to use for
language identification is application-dependent. For larger texts
(e.g., containing hundreds of words per sample), significantly smaller
ngram sets may be best. You can set the number to be used by changing
`$maxNgrams` in `TextCat.php` or in `felis.php`, or using `-m` with
`catus.php`.

### Wiki Query Models

The 30+ language models in `LM-query` are based on query data from
Wikipedia which is less formal (e.g., fewer diacritics are used in
languages that have them) and has a different distribution of words than
general text. The original set of languages considered was based on the
number of queries across all wiki projects for a particular week. The
text has been preprocessed and many queries were removed from the
training sets according to a process similar to that used on the
Wiki-Text models above.

In general, query data is much messier than Wiki-Text—including junk
text and queries in unexpected languages—but the overall performance on
query strings, at least for English Wikipedia—is better.

The final set of models provided is based in part on their performance
on English Wikipedia queries (the first target for language ID using
TextCat). For more details see our
[initial report](https://www.mediawiki.org/wiki/User:TJones_%28WMF%29/Notes/Language_Detection_with_TextCat)
on TextCat. More languages will be added in the future based on additional
performance evaluations.

These models have 10,000 ngrams. The best number of ngrams to use for
language identification is application-dependent. For larger texts
(e.g., containing hundreds of words per sample), significantly smaller
ngram sets may be best. For short query seen on English Wikipedia
strings, a model size of 3000 to 9000 ngrams has worked best, depending
on other parameter settings. You can set the number to be used by
changing `$maxNgrams` in `TextCat.php` or in `felis.php`, or using `-m`
with `catus.php`.

### Wrong-Keyboard/Encoding Models

Five of the models provided are based on "incorrect" input types, either
using the wrong keyboard, or the wrong encoding.

Wrong-keyboard input happens when someone uses two different keyboards—say
Russian Cyrillic and U.S. English—and types with the wrong one active. This
is reasonably common on Russian and Hebrew Wikipedias, for example. What
looks like gibberish—such as *,jutvcrfz hfgcjlbz*—is actually reasonable
text if the same keys are pressed on another keyboard—in this case,
*богемская рапсодия* ("bohemian rapsody"). For wrong-keyboard input, the
mapping between characters is one-to-one, so an existing model can be
converted straightforwardly.

Wrong-encoding input happens when text is encoded using one character
encoding (like [UTF-8](https://en.wikipedia.org/wiki/UTF-8)) but is
interpreted as a different character encoding (such as
[Windows-1251](https://en.wikipedia.org/wiki/Windows-1251)), which results
in something like *Москва* ("Moscow") being rendered as *РњРѕСЃРєРІР°.*
Since the character mapping is 1-to-2 (e.g., *М* → *Рњ*), the model needs
to be regenerated from incorrectly encoded sample text.

The provided wrong-keyboard/encoding models are:

* `en_cyr.lm` (in both wiki-text and wiki query versions)—English as
  accidentally typed on a Russian Cyrillic keyboard.
* `ru_lat.lm` (in both wiki-text and wiki query versions)—Russian as
  accidentally typed on a U.S. English keyboard.
* `ru_win1251.lm` (only in a wiki-text version)—UTF-8 Russian accidentally
  interpreted as being encoded in Windows-1251.

Depending on the application, the `en_cyr` and `ru_lat` models can be used
to detect non-English Latin or non-Russian Cyrillic input typed on the
wrong keyboard. For example, French or Spanish typed on the Russian
Cyrillic keyboard is much closer to the `en_cyr` model than it is to the
Russian model.