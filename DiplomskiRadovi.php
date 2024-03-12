<?php
    class DiplomskiRad
    {
        private $_naziv_rada = NULL;
        private $_tekst_rada = NULL;
        private $_link_rada = NULL;
        private $_oib_tvrtke = NULL;

        function __construct($data)
        {
            $this->_naziv_rada = $data['naziv_rada'];
            $this->_tekst_rada = $data['tekst_rada'];
            $this->_link_rada = $data['link_rada'];
            $this->_oib_tvrtke = $data['oib_tvrtke'];
        }

        // Getters
        public function getNazivRada() {
            return $this->_naziv_rada;
        }

        public function getTekstRada() {
            return $this->_tekst_rada;
        }

        public function getLinkRada() {
            return $this->_link_rada;
        }

        public function getOibTvrtke() {
            return $this->_oib_tvrtke;
        }
    }

    interface iRadovi
    {
        public function create();
        public function save();
        public function read();
    }

    class DiplomskiRadoviRepozitorij implements iRadovi
    {
        private $_diplomskiRadovi = [];
        private $_broj_stranice = 1;
        private $_conn = NULL;

        function __construct($broj_stranice)
        {
            $this->_broj_stranice = $broj_stranice;

            $servername = "localhost";
            $username = "user";
            $password = "lozinka";
            $database = "radovi";

            $this->_conn = new mysqli($servername, $username, $password, $database);

            if ($this->_conn->connect_error) {
                die("Neuspješno spajanje na bazu: " . $this->_conn->connect_error);
            }
        }

        function create()
        {
            $this->dohvatiPodatke();
        }

        function save()
        {
            foreach($this->_diplomskiRadovi as $diplomski_rad){
                $sql = "INSERT INTO diplomski_radovi (naziv_rada, tekst_rada, link_rada, oib_tvrtke) VALUES ('" . $diplomski_rad->getNazivRada() . "', '" . $diplomski_rad->getTekstRada() . "', '" . $diplomski_rad->getLinkRada() . "', '" . $diplomski_rad->getOibTvrtke() . "')";

                if ($this->_conn->query($sql) !== TRUE) {
                    echo "Greška: " . $sql . "<br>" . $this->_conn->error;
                }
            }
        }

        function read()
        {
            $sql = "SELECT * FROM diplomski_radovi";
            $result = $this->_conn->query($sql);

            if ($result->num_rows > 0) {
                // Iteriranje kroz rezultate i ispis
                while($row = $result->fetch_assoc()) {
                    echo "<br><strong>Naziv rada</strong>: " . $row["naziv_rada"] . "<br><strong>Tekst rada:</strong> " . $row["tekst_rada"] . "<br><strong>Link rada:</strong> " . $row["link_rada"] . "<br><strong>OIB tvrtke:</strong> " . $row["oib_tvrtke"] . "<br>";
                }
            } else {
                echo "Nema rezultata.";
            }
        }

        function dohvatiPodatke()
        {   
            $url = "https://stup.ferit.hr/index.php/zavrsni-radovi/page/$this->_broj_stranice/";
            $html = file_get_contents($url);

            $dom = new DOMDocument();
            @$dom->loadHTML($html); 

            $elements = $dom->getElementsByTagName('article');
            foreach ($elements as $element) {
                //NAZIV RADA
                $naziv = $element->getElementsByTagName('h2')->item(0)->nodeValue;
                
                //LINK RADA
                $link = $element->getElementsByTagName('h2')->item(0)->getElementsByTagName('a')->item(0)->getAttribute('href');

                //TEKST RADA
                $htmlRada = file_get_contents($link);
                $domRada = new DOMDocument();
                @$domRada->loadHTML($htmlRada); 
                $elementiRada = $domRada->getElementsByTagName('section')->item(0)->getElementsByTagName('p');
                
                $tekstRada = '';
                foreach($elementiRada as $paragrafi){
                    $tekstRada = $tekstRada . "<br> " . $paragrafi->nodeValue;
                }
                

                //OIB
                $img = $element->getElementsByTagName('img')->item(0)->getAttribute('src');
                preg_match('/[0-9]+/', $img, $matches);
                $oib = $matches[0];

                $noviRad = array(
                    'naziv_rada' => $naziv,
                    'tekst_rada' => $tekstRada,
                    'link_rada' => $link,
                    'oib_tvrtke' => $oib
                );

                $this->_diplomskiRadovi[] = new DiplomskiRad($noviRad);
            }
        }

    }

    //TEST PROGRAMA
    $radoviRepozitorij = new DiplomskiRadoviRepozitorij(3); //prosljeđujemo redni broj stranice s koje se dohvaćaju podaci

    //Dovat podataka
    $radoviRepozitorij->create();

    //Spremanje podataka
    $radoviRepozitorij->save();

    //Ispis podataka
    $radoviRepozitorij->read();
?>
