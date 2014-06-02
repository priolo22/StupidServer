<?php
require_once 'session_helper.php';
require_once '../models/core/Collection.php';

require_once '../models/Service.php';
require_once '../models/Client.php';
require_once '../models/ServiceClient.php';

begin_session_helper();

$url = $_GET['url'];
$params = explode("/", $url);
$action = $params[0];
$subject = $params[1];



try {

    switch ($action) {

        case "test":
            $cs = new ServiceClient();
            $cs->db->set($input);
            $data = $cs->db->GetCollection()->whereAnd("service")->whereAnd("client")->getArray();
            echo var_dump($data);
        break;
        
        // REGISTRA IL SERVICECLIENT PASSATO COME PARAMETRO
        case "service.ee.queue.register":
        
            $cs = new ServiceClient();
            
            // setto i dati di ingresso
            $cs->db->set($input);
            // carico gli oggetti corrispondenti
            $data = $cs->db->GetCollection()->whereAnd("service")->whereAnd("client")->getArray();
            
            // se ci sono risultati allora setta e restituisci
            // il lcient era gia' registrato a questo servizio
            if ( count($data) > 0 ) {
                $cs->db->set($data[0]);
                //$output->data = $cs->db->get(); 
                
            // altrimenti il client è nuovo a questo servizio...
            // creo il ServiceClient
            } else {
                // carico i dati del service
                $cs->service->db->loadDB();
                // setto il valore di last position che sarebbe il numero che il client ritira
                $cs->position = $cs->service->lastPosition;
                // incremento il lastPosition del service per il prossimo client
                // last position indica la posizione della coda dell'ultimo client
                $cs->service->lastPosition++;
        
                // aggiorno il service
                $cs->service->db->updateDB();
                // aggiorno il servizio eliminacode
                $cs->db->updateDB();
            }  
            
// ATTENZIONE QUESTOVA MESSO NEL BLOCCO IF SE IL SERVICECLIENT è NUOVO            
            // carico il service abbinato
            $cs->service->db->loadDB(array("clientAdmin"));
            // carico il client amministratore del service
            //$clientAdmin = $cs->service->clientAdmin;
            //$clientAdmin->db->loadDB();
            // invio all'amministratore il messaggio che un nuovo client s'e' collegato al servizio
            $gcm = new GCM();
            $message = array(
                "command" => "register.new",
                "data" => json_encode($cs->db->get()),
            );
            
            $gcm->send_notification(array($cs->service->clientAdmin->gcm_id), $message);
// ATTENZIONE QUESTOVA MESSO NEL BLOCCO IF SE IL SERVICECLIENT è NUOVO 
//         
            // restituisco
            $output->data = $cs->db->get(); 
        break;
        
        // CANCELLA IL SERVICE CLIENT PASSATO COME PARAMETRO
        case "service.ee.queue.unregister":
            $cs = new ServiceClient();
            $cs->db->set($input);
            $output->data = $cs->db->GetCollection()->similar()->delete();
            
            //$cs->db->deleteDB();
            //$output->data = $cs->db->get();
        break;

        // AVANTI IL PROSSIMO!!! in pratica:
        // - incremento la posizione corrente della coda 
        // - notifica il cambiamento a tutti i client registrati al servizioclient
        // - notifica anche al client che amministra questo servizio
        // richiede in input il service da far AVANZARE
        case "service.ee.queue.push":

            // carico dal DB il servizio
            $service = new Service();
            $service->db->set($input);
            $service->db->loadDB(array("clientAdmin"));
            
            // creo il servizio eliminacode
            $sc = new ServiceClient();
            $sc->service = $service;
            
            // carico i dati del servizio eliminacode fermorestando il server.
            $scs = $sc->db->GetCollection()->load("client")->whereAnd("service")->getData();

            // ciclo tutti i client-service per vedere quale è la prossima posizione da settare
            $min_dist = 0;
            foreach ( $scs as $item_sc ) {
                $dist = $item_sc->position - $service->currentPosition;
                // se è maggiore di zero allora la posizione è da controllare...
                if ( $dist > 0 ) {
                    $min_dist = $min_dist==0 ? $dist : min($dist, $min_dist);
                // questo è un servizio vecchio. Deve essere eliminato
                } else {
                    //$item_sc->db->deleteDB();
                }
            }

            // incremento di uno "currentPosition" e aggiorno sul db
            $service->currentPosition += $min_dist;
            $service->db->updateDB();
            
            // messaggio da inviare
            $message = array(
                "command" => "push",
                "target" => $service->id,
                "data" => $service->currentPosition,
            );
            
            // memorizzo tutti gli id a cui mandare il messaggio
            $clientsId = array();
            foreach ( $scs as $scItem ) {
                $clientsId[] = $scItem->client->gcm_id;
            }
            
            // inseirisco anche il rifid del client amministratore del servizio
            if (in_array($service->clientAdmin->gcm_id, $clientsId) == FALSE ) {
                $clientsId[] = $service->clientAdmin->gcm_id;
            }
           
            // invio il messaggio a tutti i client
            $gcm = new GCM();
            $gcm->send_notification($clientsId, $message);
            
        break;

        case "service.ee.clientservice.get":
            
        break;

    //-----------------------------------------------

    // seleziona tutti gli oggetti dal db
        case "select":
            $c = new Collection($subject);
            $output->data = $c->getArray();
        break;
    
    // carica l'oggetto tramite l'id.
    // puo' essere sostituito da una collection similar
        case "load":
            $model = new $subject();
            $model->db->set($input);
            $model->db->loadDB();
            $output->data = $model->db->get(); 
        break;
    
    // carica un oggetto dal db utilizzando nella where tutti i parametri settati
        case "similar":
            $model = new $subject();
            //$output->data = $input;
            $model->db->set($input);
            $output->data = $model->db->GetCollection()->similar()->getArray();            
        break;

        case "update":
            $model = new $subject();
            $model->db->set($input);
            $model->db->updateDB();
            $output->data = $model->db->get(); 
        break;

        case "del":
            $model = new $subject();
            $model->db->set($input);
            $model->db->deleteDB();
            $output->data = $model->db->get(); 
        break;

    }

} catch (Exception $ex) {
    $output->type = "error";
    $output->err = $ex->getMessage();
} 



end_session_helper();