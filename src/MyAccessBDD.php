<?php
include_once("AccessBDD.php");

/**
 * Classe de construction des requêtes SQL
 * hérite de AccessBDD qui contient les requêtes de base
 * Pour ajouter une requête :
 * - créer la fonction qui crée une requête (prendre modèle sur les fonctions 
 *   existantes qui ne commencent pas par 'traitement')
 * - ajouter un 'case' dans un des switch des fonctions redéfinies 
 * - appeler la nouvelle fonction dans ce 'case'
 */
class MyAccessBDD extends AccessBDD {
	    
    /**
     * constructeur qui appelle celui de la classe mère
     */
    public function __construct(){
        try{
            parent::__construct();
        }catch(\Exception $e){
            throw $e;
        }
    }

    /**
     * demande de recherche
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return array|null tuples du résultat de la requête ou null si erreur
     * @override
     */	
    protected function traitementSelect(string $table, ?array $champs) : ?array{
        switch($table){  
            case "livre" :
                if (!empty($champs) && isset($champs['id'])) {
                    return $this->selectLivre($champs);
                }
                return $this->selectAllLivres();
            case "dvd" :
                if (!empty($champs) && isset($champs['id'])) {
                    return $this->selectDvd($champs);
                }
                return $this->selectAllDvd();
            case "revue" :
                return $this->selectAllRevues();
            case "exemplaire" :
                return $this->selectExemplairesRevue($champs);
            case "commandes":
                if (!empty($champs) && isset($champs['id'])) {
                    return $this->selectCommandesDocuments($champs);
                } elseif (!empty($champs) && isset($champs['type'])) {
                    return $this->selectAllCommandesDocumentsDeType($champs);
                }
            case "suivi":
                return $this->selectTableSimple($table);
            case "genre":
            case "public":
            case "rayon":
            case "etat":

            case "" :
                // return $this->uneFonction(parametres);
            default:
                // cas général
                return $this->selectTuplesOneTable($table, $champs);
        }	
    }

    /**
     * demande d'ajout (insert)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples ajoutés ou null si erreur
     * @override
     */	
    protected function traitementInsert(string $table, ?array $champs) : ?int{
        switch($table){
            case "livre":
                return $this->ajouterLivre($champs);
            case "dvd":
                return $this->ajouterDvd($champs);
            case "revue":
                return $this->ajouterRevue($champs);
            case "commande":
                return $this->ajouterCommandeDocument($champs);
            case "" :
                // return $this->uneFonction(parametres);
            default:                    
                // cas général
                return $this->insertOneTupleOneTable($table, $champs);	
        }
    }
    
    /**
     * demande de modification (update)
     * @param string $table
     * @param string|null $id
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples modifiés ou null si erreur
     * @override
     */	
    protected function traitementUpdate(string $table, ?string $id, ?array $champs) : ?int{
        switch($table){
            case "livre":
                return $this->modifierLivre($champs);
            case "dvd":
                return $this->modifierDvd($champs);
            case "revue":
                return $this->modifierRevue($champs);
            case "commande":
                return $this->modifierCommandeDocument($champs);
            case "" :
                // return $this->uneFonction(parametres);
            default:                    
                // cas général
                return $this->updateOneTupleOneTable($table, $id, $champs);
        }	
    }  
    
    /**
     * demande de suppression (delete)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples supprimés ou null si erreur
     * @override
     */	
    protected function traitementDelete(string $table, ?array $champs) : ?int{
        switch($table){
            case "livre":
                return $this->supprimerLivreDvdRevue($champs);
            case "dvd":
                return $this->supprimerLivreDvdRevue($champs);
            case "revue":
                return $this->supprimerLivreDvdRevue($champs, livre_dvd: false);
            case "commande":
                return $this->supprimerCommandeDocument($champs);
            case "" :
                // return $this->uneFonction(parametres);
            default:                    
                // cas général
                return $this->deleteTuplesOneTable($table, $champs);	
        }
    }	    
        
    /**
     * récupère les tuples d'une seule table
     * @param string $table
     * @param array|null $champs
     * @return array|null 
     */
    private function selectTuplesOneTable(string $table, ?array $champs) : ?array{
        if(empty($champs)){
            // tous les tuples d'une table
            $requete = "select * from $table;";
            return $this->conn->queryBDD($requete);  
        }else{
            // tuples spécifiques d'une table
            $requete = "select * from $table where ";
            foreach ($champs as $key => $value){
                $requete .= "$key=:$key and ";
            }
            // (enlève le dernier and)
            $requete = substr($requete, 0, strlen($requete)-5);	          
            return $this->conn->queryBDD($requete, $champs);
        }
    }	

    /**
     * demande d'ajout (insert) d'un tuple dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples ajoutés (0 ou 1) ou null si erreur
     */	
    private function insertOneTupleOneTable(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        // construction de la requête
        $requete = "insert into $table (";
        foreach ($champs as $key => $value){
            $requete .= "$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ") values (";
        foreach ($champs as $key => $value){
            $requete .= ":$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ");";
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * demande de modification (update) d'un tuple dans une table
     * @param string $table
     * @param string\null $id
     * @param array|null $champs 
     * @return int|null nombre de tuples modifiés (0 ou 1) ou null si erreur
     */	
    private function updateOneTupleOneTable(string $table, ?string $id, ?array $champs) : ?int {
        if(empty($champs)){
            return null;
        }
        if(is_null($id)){
            return null;
        }
        // construction de la requête
        $requete = "update $table set ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);				
        $champs["id"] = $id;
        $requete .= " where id=:id;";		
        return $this->conn->updateBDD($requete, $champs);	        
    }
    
    /**
     * demande de suppression (delete) d'un ou plusieurs tuples dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples supprimés ou null si erreur
     */
    private function deleteTuplesOneTable(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        // construction de la requête
        $requete = "delete from $table where ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key and ";
        }
        // (enlève le dernier and)
        $requete = substr($requete, 0, strlen($requete)-5);   
        return $this->conn->updateBDD($requete, $champs);	        
    }
 
    /**
     * récupère toutes les lignes d'une table simple (qui contient juste id et libelle)
     * @param string $table
     * @return array|null
     */
    private function selectTableSimple(string $table) : ?array{
        $requete = "select * from $table order by libelle;";		
        return $this->conn->queryBDD($requete);	    
    }
    
    /**
     * récupère toutes les lignes de la table Livre et les tables associées
     * @return array|null
     */
    private function selectAllLivres() : ?array{
        $requete = "Select l.id, l.ISBN, l.auteur, d.titre, d.image, l.collection, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from livre l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";		
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère une ligne de la table Livre et les tables associées par id
     * @return array|null
     */
    private function selectLivre($champs): ?array
    {
        if (empty($champs)) {
            return null;
        }
        if (!array_key_exists('id', $champs)) {
            return null;
        }
        $requete = "Select l.id, l.ISBN, l.auteur, d.titre, d.image, l.collection, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from livre l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "where l.id=:id ";
        $requete .= "order by titre ";
        $champsRequete['id'] = $champs['id'];
        return $this->conn->queryBDD($requete, $champsRequete);
    }

    /**
     * récupère toutes les lignes de la table DVD et les tables associées
     * @return array|null
     */
    private function selectAllDvd(): ?array
    {
        $requete = "Select l.id, l.duree, l.realisateur, d.titre, d.image, l.synopsis, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from dvd l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère une ligne de la table Dvd et les tables associées par id
     * @return array|null
     */
    private function selectDvd($champs): ?array
    {
        if (empty($champs)) {
            return null;
        }
        if (!array_key_exists('id', $champs)) {
            return null;
        }
        $requete = "Select l.id, l.duree, l.realisateur, d.titre, d.image, l.synopsis, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from dvd l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "where l.id=:id ";
        $requete .= "order by titre ";
        $champsRequete['id'] = $champs['id'];
        return $this->conn->queryBDD($requete, $champsRequete);
    }

    /**
     * récupère toutes les lignes de la table Revue et les tables associées
     * @return array|null
     */
    private function selectAllRevues() : ?array{
        $requete = "Select l.id, l.periodicite, d.titre, d.image, l.delaiMiseADispo, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from revue l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }	

    /**
     * récupère tous les exemplaires d'une revue
     * @param array|null $champs
     * @return array|null
     */
    private function selectExemplairesRevue(?array $champs) : ?array{
        if(empty($champs)){
            return null;
        }
        if(!array_key_exists('id', $champs)){
            return null;
        }
        $champNecessaire['id'] = $champs['id'];
        $requete = "Select e.id, e.numero, e.dateAchat, e.photo, e.idEtat ";
        $requete .= "from exemplaire e join document d on e.id=d.id ";
        $requete .= "where e.id = :id ";
        $requete .= "order by e.dateAchat DESC";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * Recupère toutes les commandes pour les livres et dvds
     * @return array|null
     */
    private function selectAllCommandesDocumentsDeType($champs) : ?array {
        if (empty($champs)) {
            return null;
        }
        if (!array_key_exists('type', $champs)) {
            return null;
        }
        $requete = "
        SELECT cd.id, cd.nbExemplaire, cd.idLivreDvd, cd.idSuivi, s.libelle as suivi, c.dateCommande, c.montant 
        FROM commandedocument cd 
        join commande c on(cd.id=c.id) 
        join suivi s on (cd.idSuivi=s.id) 
        WHERE cd.idLivreDvd like :type 
        ORDER BY c.dateCommande DESC";
        if ($champs['type'] == "livre") {
            $champsRequete['type'] = "0%";
        } else {
            $champsRequete['type'] = "2%";
        }
        return $this->conn->queryBDD($requete, $champsRequete);
    }

    /**
     * Recupère toutes les commandes pour les livres et dvds
     * @return array|null
     */
    private function selectCommandesDocuments($champs): ?array
    {
        if (empty($champs)) {
            return null;
        }
        if (!array_key_exists('id', $champs)) {
            return null;
        }
        $requete = "
        SELECT cd.id, cd.nbExemplaire, cd.idLivreDvd, cd.idSuivi, s.libelle as suivi, c.dateCommande, c.montant 
        FROM commandedocument cd 
        join commande c on(cd.id=c.id) 
        join suivi s on (cd.idSuivi=s.id) 
        WHERE cd.idLivreDvd = :id 
        ORDER BY c.dateCommande DESC";
        $champsRequete['id'] = $champs['id'];
        return $this->conn->queryBDD($requete, $champsRequete);
    }

    /**
     * Enregister une nouvelle commande de livre ou dvd
     * @param mixed $champs
     * @return int|null
     */
    private function ajouterCommandeDocument($champs) {
        if (empty($champs)) {
            return null;
        }
        // vérifier champs obligatoires
        if (
            !array_key_exists('IdLivreDvd', $champs) ||
            !array_key_exists('IdSuivi', $champs) ||
            !array_key_exists('NbExemplaire', $champs) ||
            !array_key_exists('DateCommande', $champs) ||
            !array_key_exists('Montant', $champs)
        ) {
            return null;
        }

        // obtenir le prochain id
        $requete = "SELECT MAX(id) AS max_id FROM commande;";
        $resultat = $this->conn->queryBDD($requete);
        if ($resultat && !empty($resultat)) {
            $maxId = $resultat[0]['max_id'];
            $id = str_pad(((string) ((int) $maxId + 1)), 5, "0", STR_PAD_LEFT);
        } else {
            $id = "00001";
        }

        // construction de requête
        $requete = "
        START TRANSACTION;

        INSERT INTO commande (id, dateCommande, montant) 
        VALUES (:Id, :DateCommande, :Montant);

        INSERT INTO commandedocument (id, nbExemplaire, idLivreDvd, idSuivi) 
        VALUES (:Id, :NbExemplaire, :IdLivreDvd, :IdSuivi);

        COMMIT;
        ";

        $champsRequete['Id'] = $id;
        $champsRequete['DateCommande'] = $champs['DateCommande'];
        $champsRequete['Montant'] = $champs['Montant'];
        $champsRequete['NbExemplaire'] = $champs['NbExemplaire'];
        $champsRequete['IdLivreDvd'] = $champs['IdLivreDvd'];
        $champsRequete['IdSuivi'] = $champs['IdSuivi'];

        return $this->conn->updateBDD($requete, $champsRequete);
    }

    /**
     * Modifier une commande de livre ou dvd (seulement l'étape de suivi)
     * @param mixed $champs
     * @return int|null
     */
    private function modifierCommandeDocument($champs)
    {
        if (empty($champs)) {
            return null;
        }
        // vérifier champs obligatoires
        if (
            !array_key_exists('Id', $champs) ||
            !array_key_exists('IdSuivi', $champs)
        ) {
            return null;
        }

        // construction de requête
        $requete = "
        START TRANSACTION;

        UPDATE commandedocument 
        SET idSuivi = :IdSuivi 
        WHERE id = :id;

        COMMIT;
        ";

        $champsRequete['id'] = $champs['Id'];
        $champsRequete['IdSuivi'] = $champs['IdSuivi'];

        return $this->conn->updateBDD($requete, $champsRequete);
    }

    /**
     * Supprimer une commande de livre ou dvd
     * @param mixed $champs
     * @return int|null
     */
    public function supprimerCommandeDocument($champs) {
       if (empty($champs)) {
            return null;
        }
        // vérifier champs obligatoires
        if (!array_key_exists('id', $champs)) {
            return null;
        }
        $champsRequete['id'] = $champs['id'];

        // construction de requête
        $requete = "
        START TRANSACTION;

        DELETE FROM commandedocument WHERE id=:id; 
        DELETE FROM commande WHERE id=:id;

        COMMIT;
        ";

        return $this->conn->updateBDD($requete, $champsRequete); 
    }

    /**
     * Ajouter un livre dans la BDD
     * @param array $champs
     * @return int|null
     */
    private function ajouterLivre(array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }
        // vérifier champs obligatoires
        if (
            !array_key_exists('IdRayon', $champs) ||
            !array_key_exists('IdPublic', $champs) ||
            !array_key_exists('IdGenre', $champs) ||
            !array_key_exists('Titre', $champs)
        ) {
            return null;
        }

        // obtenir le prochain id
        $requete = "SELECT MAX(id) AS max_id FROM livre;";
        $resultat = $this->conn->queryBDD($requete);
        if ($resultat && !empty($resultat)) {
            $maxId = $resultat[0]['max_id'];
            $id = str_pad(((string) ((int) $maxId + 1)), 5, "0", STR_PAD_LEFT);
        } else {
            $id = "00001";
        }

        // construction de requête
        $requete = "
        START TRANSACTION;

        INSERT INTO document (id, titre, image, idRayon, idPublic, idGenre) 
        VALUES (:id, :titre, :image, :rayon, :public, :genre);

        INSERT INTO livres_dvd (id) VALUES (:id);

        INSERT INTO livre (id, ISBN, auteur, collection) 
        VALUES (:id, :isbn, :auteur, :collection);

        COMMIT;
        ";

        $champsRequete['id'] = $id;
        $champsRequete['titre'] = $champs['Titre'];
        $champsRequete['image'] = $champs['Image'];
        $champsRequete['rayon'] = $champs['IdRayon'];
        $champsRequete['public'] = $champs['IdPublic'];
        $champsRequete['genre'] = $champs['IdGenre'];
        $champsRequete['isbn'] = $champs['Isbn'];
        $champsRequete['auteur'] = $champs['Auteur'];
        $champsRequete['collection'] = $champs['Collection'];

        return $this->conn->updateBDD($requete, $champsRequete);
    }

    /**
     * Modifier un livre dans la BDD
     * @param array $champs
     * @return int|null
     */
    private function modifierLivre(array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }
        // vérifier champs obligatoires
        if (
            !array_key_exists('Id', $champs) ||
            !array_key_exists('IdRayon', $champs) ||
            !array_key_exists('IdPublic', $champs) ||
            !array_key_exists('IdGenre', $champs) ||
            !array_key_exists('Titre', $champs)
        ) {
            return null;
        }

        // construction de requête
        $requete = "
        START TRANSACTION;

        UPDATE document 
        SET titre = :titre, image = :image, idRayon = :rayon, idPublic = :public, idGenre = :genre
        WHERE id = :id;

        UPDATE livre 
        SET ISBN = :isbn, auteur = :auteur, collection = :collection 
        WHERE id = :id;

        COMMIT;
        ";

        $champsRequete['id'] = $champs['Id'];
        $champsRequete['titre'] = $champs['Titre'];
        $champsRequete['image'] = $champs['Image'];
        $champsRequete['rayon'] = $champs['IdRayon'];
        $champsRequete['public'] = $champs['IdPublic'];
        $champsRequete['genre'] = $champs['IdGenre'];
        $champsRequete['isbn'] = $champs['Isbn'];
        $champsRequete['auteur'] = $champs['Auteur'];
        $champsRequete['collection'] = $champs['Collection'];

        return $this->conn->updateBDD($requete, $champsRequete);
    }

    /**
     * Supprimer un document (livre, dvd ou revue) dans la BDD
     * @param array $champs
     * @param bool $livre_dvd true si le document est un livre ou un dvd, faux sinon
     * @return int|null
     */
    private function supprimerLivreDvdRevue(array $champs, bool $livre_dvd = true): ?int
    {
        if (empty($champs)) {
            return null;
        }
        // vérifier champs obligatoires
        if (!array_key_exists('Id', $champs)) {
            return null;
        }
        $champsRequete['id'] = $champs['Id'];

        // vérifier la présence d'exemplaires
        $requete = "SELECT COUNT(id) AS ex FROM exemplaire where id=:id;";
        $resultat = $this->conn->queryBDD($requete, $champsRequete);
        if ($resultat[0]['ex'] != 0) {
            return null;
        }

        if ($livre_dvd) {
            // vérifier la présence dans commandedocument
            $requete = "SELECT COUNT(id) AS com FROM commandedocument where idLivreDvd=:id;";
            $resultat = $this->conn->queryBDD($requete, $champsRequete);
            if ($resultat[0]['com'] != 0) {
                return null;
            }
        } else {
            // vérifier dans abonnement
            $requete = "SELECT COUNT(id) AS com FROM abonnement where idRevue=:id;";
            $resultat = $this->conn->queryBDD($requete, $champsRequete);
            if ($resultat[0]['com'] != 0) {
                return null;
            }
        }

        // construction de requête
        $requete = "
        START TRANSACTION;

        DELETE FROM livre WHERE id=:id;
        DELETE FROM dvd WHERE id=:id;
        DELETE FROM revue WHERE id=:id;

        DELETE FROM livres_dvd WHERE id=:id;
        
        DELETE FROM document WHERE id=:id;

        COMMIT;
        ";

        return $this->conn->updateBDD($requete, $champsRequete);
    }

    /**
     * Ajouter un dvd dans la BDD
     * @param array $champs
     * @return int|null
     */
    private function ajouterDvd(array $champs): ?int {
        if (empty($champs)) {
            return null;
        }
        // vérifier champs obligatoires
        if (
            !array_key_exists('IdRayon', $champs) ||
            !array_key_exists('IdPublic', $champs) ||
            !array_key_exists('IdGenre', $champs) ||
            !array_key_exists('Titre', $champs)
            ) {
            return null;
        }

        // obtenir le prochain id
        $requete = "SELECT MAX(id) AS max_id FROM dvd;";
        $resultat = $this->conn->queryBDD($requete);
        if ($resultat && !empty($resultat)) {
            $maxId = $resultat[0]['max_id'];
            $id = (string) ((int) $maxId + 1);
        } else {
            $id = "20001";
        }

        // construction de requête
        $requete = "
        START TRANSACTION;

        INSERT INTO document (id, titre, image, idRayon, idPublic, idGenre) 
        VALUES (:id, :titre, :image, :rayon, :public, :genre);

        INSERT INTO livres_dvd (id) VALUES (:id);

        INSERT INTO dvd (id, synopsis, realisateur, duree) 
        VALUES (:id, :synopsis, :realisateur, :duree);

        COMMIT;
        ";

        $champsRequete['id'] = $id;
        $champsRequete['titre'] = $champs['Titre'];
        $champsRequete['image'] = $champs['Image'];
        $champsRequete['rayon'] = $champs['IdRayon'];
        $champsRequete['public'] = $champs['IdPublic'];
        $champsRequete['genre'] = $champs['IdGenre'];
        $champsRequete['synopsis'] = $champs['Synopsis'];
        $champsRequete['realisateur'] = $champs['Realisateur'];
        $champsRequete['duree'] = $champs['Duree'];
        
        return $this->conn->updateBDD($requete, $champsRequete);
    }

    /**
     * Modifier un dvd dans la BDD
     * @param array $champs
     * @return int|null
     */
    private function modifierDvd(array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }
        // vérifier champs obligatoires
        if (
            !array_key_exists('Id', $champs) ||
            !array_key_exists('IdRayon', $champs) ||
            !array_key_exists('IdPublic', $champs) ||
            !array_key_exists('IdGenre', $champs) ||
            !array_key_exists('Titre', $champs)
        ) {
            return null;
        }

        // construction de requête
        $requete = "
        START TRANSACTION;

        UPDATE document 
        SET titre = :titre, image = :image, idRayon = :rayon, idPublic = :public, idGenre = :genre
        WHERE id = :id;

        UPDATE dvd 
        SET synopsis = :synopsis, realisateur = :realisateur, duree = :duree 
        WHERE id = :id;

        COMMIT;
        ";

        $champsRequete['id'] = $champs['Id'];
        $champsRequete['titre'] = $champs['Titre'];
        $champsRequete['image'] = $champs['Image'];
        $champsRequete['rayon'] = $champs['IdRayon'];
        $champsRequete['public'] = $champs['IdPublic'];
        $champsRequete['genre'] = $champs['IdGenre'];
        $champsRequete['synopsis'] = $champs['Synopsis'];
        $champsRequete['realisateur'] = $champs['Realisateur'];
        $champsRequete['duree'] = $champs['Duree'];

        return $this->conn->updateBDD($requete, $champsRequete);
    }

    /**
     * Ajouter une revue dans la BDD
     * @param array $champs
     * @return int|null
     */
    private function ajouterRevue(array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }
        // vérifier champs obligatoires
        if (
            !array_key_exists('IdRayon', $champs) ||
            !array_key_exists('IdPublic', $champs) ||
            !array_key_exists('IdGenre', $champs) ||
            !array_key_exists('Titre', $champs)
        ) {
            return null;
        }

        // obtenir le prochain id
        $requete = "SELECT MAX(id) AS max_id FROM revue;";
        $resultat = $this->conn->queryBDD($requete);
        if ($resultat && !empty($resultat)) {
            $maxId = $resultat[0]['max_id'];
            $id = (string) ((int) $maxId + 1);
        } else {
            $id = "10001";
        }

        // construction de requête
        $requete = "
        START TRANSACTION;

        INSERT INTO document (id, titre, image, idRayon, idPublic, idGenre) 
        VALUES (:id, :titre, :image, :rayon, :public, :genre);

        INSERT INTO revue (id, periodicite, delaiMiseADispo) 
        VALUES (:id, :periodicite, :delaiMiseADispo);

        COMMIT;
        ";

        $champsRequete['id'] = $id;
        $champsRequete['titre'] = $champs['Titre'];
        $champsRequete['image'] = $champs['Image'];
        $champsRequete['rayon'] = $champs['IdRayon'];
        $champsRequete['public'] = $champs['IdPublic'];
        $champsRequete['genre'] = $champs['IdGenre'];
        $champsRequete['periodicite'] = $champs['Periodicite'];
        $champsRequete['delaiMiseADispo'] = $champs['DelaiMiseADispo'];

        return $this->conn->updateBDD($requete, $champsRequete);
    }

    /**
     * Modifier une revue dans la BDD
     * @param array $champs
     * @return int|null
     */
    private function modifierRevue(array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }
        // vérifier champs obligatoires
        if (
            !array_key_exists('Id', $champs) ||
            !array_key_exists('IdRayon', $champs) ||
            !array_key_exists('IdPublic', $champs) ||
            !array_key_exists('IdGenre', $champs) ||
            !array_key_exists('Titre', $champs)
        ) {
            return null;
        }

        // construction de requête
        $requete = "
        START TRANSACTION;

        UPDATE document 
        SET titre = :titre, image = :image, idRayon = :rayon, idPublic = :public, idGenre = :genre
        WHERE id = :id;

        UPDATE revue SET 
        periodicite = :periodicite, delaiMiseADispo = :delaiMiseADispo 
        WHERE id = :id;

        COMMIT;
        ";

        $champsRequete['id'] = $champs['Id'];
        $champsRequete['titre'] = $champs['Titre'];
        $champsRequete['image'] = $champs['Image'];
        $champsRequete['rayon'] = $champs['IdRayon'];
        $champsRequete['public'] = $champs['IdPublic'];
        $champsRequete['genre'] = $champs['IdGenre'];
        $champsRequete['periodicite'] = $champs['Periodicite'];
        $champsRequete['delaiMiseADispo'] = $champs['DelaiMiseADispo'];

        return $this->conn->updateBDD($requete, $champsRequete);
    }
}
