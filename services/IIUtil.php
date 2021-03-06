<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of IIUtil
 *
 * @author iorio_000
 */
class IIUtil {
      
    /**
     * Converte una stringa in un XML (SimpleXMLObject)
     * @param type $str
     * @return type
     */
    public static function StringToXML ( $str ) {
        $use_errors = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($str);
        if (!$xml) { 
            echo "Error: Parsing xml.\n"; 
            exit; 
        }
        libxml_clear_errors();
        libxml_use_internal_errors($use_errors);   
        return $xml;
    }
    
    /**
     * converte un oggetto XML (SimpleXMLObject) in un array associativo.
     * @param type $xmlObject
     * @param type $out
     * @return type
     */
    public static function XmlToArray ( $xmlObject, $out = array () ) {
        foreach ( (array) $xmlObject as $index => $node )
            $out[$index] = ( is_object ( $node ) ) ? self::XmlToArray ( $node ) : $node;
        return $out;
    }
    
    
    public static function ObjectToXML($array, $node_name) {
	$xml = '';
	if (is_array($array) || is_object($array)) {
            foreach ($array as $key=>$value) {
                if (is_numeric($key)) {
                    $key = $node_name;
                }
                $xml .= '<' . $key . '>' . "\n" . self::ObjectToXML($value, $node_name) . '</' . $key . '>' . "\n";
            }
	} else {
            $xml = htmlspecialchars($array, ENT_QUOTES) . "\n";
	}
	return $xml;
    }

    public static function ObjectToCompleteXML($array, $node_block='nodes', $node_name='node') {
	$xml = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
	$xml .= '<' . $node_block . '>' . "\n";
	$xml .= self::ObjectToXML($array, $node_name);
	$xml .= '</' . $node_block . '>' . "\n";
	return $xml;
    }
    
    
    
    
    
    
    
    /**
     * Restituisce tutto l'albero completo di una directory
     * @param type $dir
     * @return type
     */
    public static function DirToArray($dir, $recursive=TRUE) {
        $result = array(); 
        $cdir = scandir($dir); 
        foreach ($cdir as $key => $fileName) { 
            if (in_array($fileName,array(".",".."))==TRUE) continue;
            $pathName = $dir . DIRECTORY_SEPARATOR . $fileName;
                
            if (is_dir($pathName)) { 
                $result[$fileName] = $recursive? IIUtil::DirToArray($pathName) : "<dir>"; 
            } else { 
                $result[] = $fileName; 
            } 
        } 
        return $result; 
    } 
    
    public static function DirDelete ($dirname, $withThis=true) {
        if (file_exists($dirname)==false) throw ( new Exception("IIUtil::DirDelete::no_file"));
        if (is_file($dirname)==true) return unlink($dirname);
        
        $files = scandir($dirname);
        foreach ($files as $fileName) { 
            if (in_array($fileName,array(".",".."))==true) continue;
            $pathName = $dirname . DIRECTORY_SEPARATOR . $fileName;
            
            IIUtil::DirDelete($pathName);
        }
        return $withThis==true? rmdir($dirname): true;
    }


    // Function to Copy folders and files       
    public static function DirCopy($src, $dst) {
        if (file_exists ( $dst )) IIUtil::DirDelete ( $dst );
        
        if (is_dir ( $src )) {
            if ( mkdir ( $dst ) === false ) throw new Exception ("IIUtil::DirCopy::eror_mkdir");
            $files = scandir ( $src );
            foreach ( $files as $file )
                if ($file != "." && $file != "..")
                    IIUtil::DirCopy ( "$src/$file", "$dst/$file" );
                
        } else if (file_exists ( $src )) {
            if ( copy ( $src, $dst ) === false ) throw new Exception ("IIUtil::DirCopy::eror_copy");
        }
    }
    
    
}
