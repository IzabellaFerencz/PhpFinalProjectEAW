<?php

namespace App\Helpers;
require_once(dirname(__FILE__) . '../../../autoload.php');
use App\Entity\Post;
use Zend_Search_Lucene;
use Zend_Search_Lucene_Document;
use Zend_Search_Lucene_Field;
use Zend_Search_Lucene_Search_Query_MultiTerm;


class LuceneSearcher
{
   
    /**
     * Calea catre directorul unde fisierele specifice indexarii vor fi salvate
     *
     * @return string
     */
    static public function getLuceneIndexFile() {
        return '/xampp/htdocs/simfony/an_index'; //pentru testare: schimbati la an_index_test
    }
    
    
    /**
     * Se verifica daca directorul mentionat mai sus exista,
     * in acest caz indexul este deschis,
     * altfel indexul este creat
     *
     * @return Zend_Search_Lucene
     */
    static public function getLuceneIndex() {
        if (file_exists($index = self::getLuceneIndexFile())) {
            return Zend_Search_Lucene::open($index);
        } else {
          
            return  Zend_Search_Lucene::create($index);
        }
    }
    
    
    /**
     * Crearea si/sau editarea unei intrari (un job introdus)
     * si salvarea informatiilor specifice in document
     *
     * @param job
     */
    public function updateLuceneIndex(Post $post)
     {
        $index = self::getLuceneIndex();
        
        foreach ($index -> find('key:'.$post -> getId()) as $hit) 
        {
            $index -> delete($hit -> id);
        }
        
        $doc = new Zend_Search_Lucene_Document();
        $doc -> addField(Zend_Search_Lucene_Field::Keyword('key', $post -> getId()));
        $doc -> addField(Zend_Search_Lucene_Field::Text('title', $post -> getTitle(), 'utf-8'));
        $doc -> addField(Zend_Search_Lucene_Field::Text('description', $post -> getDescription(), 'utf-8'));
        $doc -> addField(Zend_Search_Lucene_Field::Text('status', $post -> getStatus(), 'utf-8'));
  
        $index -> addDocument($doc);
        $index -> commit();
    }
    
    
    // /**
    //  * Stergerea unui index corespunzator unui job
    //  *
    //  * @param job
    //  */
    // public function deleteLuceneIndex(patient $patient) {
    //     $index = self::getLuceneIndex();
        
    //     foreach ($index -> find('key:'.$patient -> getId()) as $hit){
    //         $index -> delete($hit -> id);
    //     }
    // }
    
    
    // // extra metode
    // /**
    //  * Verificarea existentei unui index cu o cheie specifica
    //  *
    //  * @param indexNr
    //  */
    // public function countIndex($indexNr) {
    //     $index = self::getLuceneIndex();
    //     if ($index -> find('key:'.$indexNr)) {
    //         return true;
    //     } else {
    //         return false;
    //     }
    // }
    
    
    // /**
    //  * Verificarea unui job daca se gaseste in index,
    //  * in functie de descriere
    //  *
    //  * @param job
    //  * @return string
    //  */
    // public function getIndexOfASpecificJob(patient $patient) {
    //     $searchTerm = $patient -> getName();
    //     $hits = self::getLuceneIndex() -> find($searchTerm);
        
    //     $result ="";
    //     foreach ($hits as $hit) {
    //         if ($hit -> key == $patient -> getId()) {
    //             $result = $hit -> key;
    //             break;
    //         }
    //     }
        
    //     return $result;
    // }
    
    
    // /**
    //  * Adaugarea unui element care nu va fi afisat la cautare
    //  *
    //  * @param job
    //  * @return string
    //  */
    // public function addProhibitedJobTerm(patient $patient) {
    //     $index = self::getLuceneIndex();
        
    //     $query = new Zend_Search_Lucene_Search_Query_MultiTerm($patient -> getName(), '-');
    //     $hits  = $index -> find($query);
        
    //     $result = "";
    //     foreach ($hits as $hit) {
    //         if ($hit -> key == $patient -> getId()) {
    //             $result = $hit -> key;
    //             break;
    //         }
    //     }
        
    //     return $result;
    // }
}