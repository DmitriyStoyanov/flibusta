<?php
header('Content-Type: application/atom+xml; charset=utf-8');
echo '<?xml version="1.0" encoding="utf-8"?>';
echo <<< _XML
 <feed xmlns="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/terms/" xmlns:os="http://a9.com/-/spec/opensearch/1.1/" xmlns:opds="http://opds-spec.org/2010/catalog"> <id>tag:root:authors</id>
 <title>Книги по авторам</title>
 <updated>2019-02-08T18:11:20+01:00</updated>
 <icon>/favicon.ico</icon>
 <link href="/opds-opensearch.xml" rel="search" type="application/opensearchdescription+xml" />
 <link href="/search?q={searchTerms}" rel="search" type="application/atom+xml" />
 <link href="/" rel="start" type="application/atom+xml;profile=opds-catalog" />
_XML;

$q = $_GET['q'];
$get = "?q=$q";

if ($q == '') {
	die(':(');
}

//$filter2 = "AND libbook.Title LIKE " . DB::es('%' . $q . '%');

$books = $dbh->prepare("SELECT DISTINCT BookId, libbook.Title as BookTitle,
        (SELECT Body FROM libbannotations WHERE BookId=libbook.BookId LIMIT 1) as Body
		FROM libbook
		JOIN libgenre USING(BookId) 
		WHERE deleted='0' AND libbook.Title LIKE :q
		GROUP BY BookId, BookTitle, Body
		LIMIT 100");
		$param = '%'.$q.'%';
$books->bindParam(":q", $param);
$books->execute();

while ($b = $books->fetchObject()) {
	echo " <entry> <updated>2019-02-08T21:53:29+01:00</updated>";
	echo " <id>tag:book:$b->bookid</id>";
	echo " <title>" . htmlspecialchars($b->booktitle) . "</title>";

	$as = '';
	$authors = $dbh->query("SELECT lastname, firstname, middlename FROM libavtorname, libavtor WHERE libavtor.BookId=$b->bookid AND libavtor.AvtorId=libavtorname.AvtorId ORDER BY LastName");
	while ($a = $authors->fetchObject()) {
		$as .= $a->lastname . " " . $a->firstname . " " . $a->middlename . ", ";
	}
	$authors = null;

	echo "<author> <name>$as</name>";
	echo " <uri>/a/id</uri>";
	echo "</author>";
	echo " <content type='text/html'>" . htmlspecialchars($b->body ?? '') . "</content>";

	echo "<link rel='http://opds-spec.org/image/thumbnail' href='http://192.168.32.5/lib/get_cover.php?id=$b->bookid' type='image/jpeg'/>";
	echo "<link rel='http://opds-spec.org/image' href='http://192.168.32.5/lib/get_cover.php?id=$b->bookid' type='image/jpeg'/>";
	echo " <link href='http://192.168.32.5/lib/get_fb2.php?id=$b->bookid' rel='http://opds-spec.org/acquisition/open-access' type='application/fb2+zip' />";

	echo "</entry>\n";
}
$books = null;
?>
</feed>