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
            
            $s = new Service();
            $s->db->set($input);
            
            $sc = new ServiceClient();
            $sc->service = $s;
            $output->data = $sc->db->getCollection()->load("service")->getData();
            
            
            
            
            /*
             * SELECT "similar"
            $sc = new ServiceClient();
            $sc->db->set($input);
            $output->data = $sc->db->getCollection()->similar(array("service"))->getArray();
            */
            
            
            /*
             * CGM
             */
            /*
            $client = new Client();
            $client->db->set($input);
            $client->db->loadDB();
            
            //$clientAdmin->db->loadDB();
            // invio all'amministratore il messaggio che un nuovo client s'e' collegato al servizio
            $gcm = new GCM();
            $message = array(
                "command" => "client.test",
                "data" => json_encode($client->db->get()),
            );
            
            $gcm->send_notification(array($client->gcm_id), $message);
            */
            
            
            
            
            /*
            $cs = new ServiceClient();
            $cs->db->set($input);
            $data = $cs->db->getCollection()->whereAnd("service")->whereAnd("client")->getArray();
            echo var_dump($data);
             * 
             */
        break;
        
        // REGISTRA IL SERVICECLIENT PASSATO COME PARAMETRO
        // in pratica quando prendo un biglietto
        case "service.ee.queue.register":
        
            // creo il ServiceClient...
            $cs = new ServiceClient();
            // ... e setto i dati di ingresso
            $cs->db->set($input);
            // carico gli oggetti corrispondenti
            $data = $cs->db->getCollection()->whereAnd("service")->whereAnd("client")->getArray();
            
            // se ci sono risultati allora setta e restituisci
            // vuol dire che il client era gia' registrato a questo servizio
            if ( count($data) > 0 ) {
                $cs->db->set($data[0]);
                //$output->data = $cs->db->get(); 
                
            // altrimenti il client è nuovo a questo servizio...
            // creo il ServiceClient
            } else {
                // carico i dati del service
                $cs->service->db->loadDB();
                // setto il valore di last position che sarebbe il numero che il client ritira, cioe' il valore "lastPosition" del "service"
                $cs->position = $cs->service->lastPosition;
                // incremento il lastPosition del service per il prossimo client last position indica la posizione della coda dell'ultimo client
                $cs->service->lastPosition++;
                // aggiorno il service sul DB
                $cs->service->db->updateDB();
                // aggiorno il servizio eliminacode sul DB
                $cs->db->updateDB();
                
                // carico il service abbinato
                $cs->service->db->loadDB(array("clientAdmin"));
                
                // carico il client amministratore del service
                //$clientAdmin = $cs->service->clientAdmin;
                //$clientAdmin->db->loadDB();
                // invio all'amministratore il messaggio che un nuovo client s'e' collegato al servizio
                $gcm = new GCM();
                
                // messaggio da inviare
                $jsonMessage = json_encode(array(
                    "command" => "service.ee.queue.register",
                    "data" => json_encode($cs->db->get()),
                ));
                $message = array( "data" => $jsonMessage );                
                
                $gcm->send_notification(array($cs->service->clientAdmin->gcm_id), $message);
            }
            
            // restituisco
            $output->data = $cs->db->get(); 
        break;
        
        // CANCELLA IL SERVICE CLIENT PASSATO COME PARAMETRO
        // praticamente quando uno rinuncia al biglietto
        case "service.ee.queue.unregister":
            $cs = new ServiceClient();
            $cs->db->set($input);
            $cs->db->getCollection()->whereAnd("client")->whereAnd("service")->delete();
            $output->data = $cs->db->get();
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
            $scs = $sc->db->getCollection()->load("client")->whereAnd("service")->getData();

            // ciclo tutti i client-service per vedere quale è la prossima posizione da settare
            $min_dist = 0;
            foreach ( $scs as $item_sc ) {
                $dist = $item_sc->position - $service->currentPosition;
                // se è maggiore di zero allora la posizione è da controllare...
                if ( $dist > 0 ) {
                    $min_dist = $min_dist==0 ? $dist : min($dist, $min_dist);
                // questo è un servizio vecchio. Deve essere eliminato
                } else {
// ATTENZIONE: abilitare il delete                    
                    //$item_sc->db->delete();
                }
            }
            
            // AGGIORNAMENTI SERVICE
            // se la $min_dist è maggiore di ZERO ci sono altri clienti altrimenti no 
            if ( $min_dist > 0 ) {
                // incremento di uno "currentPosition" e aggiorno sul db
                $service->currentPosition += $min_dist;
                // aggiorno il tempo di lavoro
                $service->workTimeTotal += (int)$input["time_for_work"];
                // e il numero di utenti servito
    // ATTENZIONE gestire il caso in cui uno fa il push su CODA VUOTA!!! perche' ora aggiunge sempre un customer al totale anche se la coda è vuota ma è utile per il debug            
                $service->workCustomersServed++;
                // aggiorno il servizio sul DB
                $service->db->updateDB();
            }
            
            // INVIO MESSAGGIO GCM
            // invio comunque il messaggio anche a coda vuota perche' cosi' l'admin lo riceve.
            // messaggio da inviare
            $jsonMessage = json_encode(array(
                "command" => "service.ee.queue.push",
                "service" => $service->id,
                "cp" => $service->currentPosition,
            ));
            $message = array( "data" => $jsonMessage );
            
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
            
            // restituisco il service
            $output->data = $service->db->get(); 
            
        break;

        // ELIMINA UN SERVIZIO E TUTTI I SERVICE_CLIENT COLLEGATI
        case "service.ee.destroy":
            
            $s = new Service();
            $s->db->set($input);

            // id dei client registrati a questo servizio
            $gcm_ids = array();
            
            // prelevo gli id e cancello i collegamenti dei client a questo servizio
            $cs = new ServiceClient();
            $cs->service = $s;
            $cs->db->getCollection()->similar(array("service"))->load("client")
                ->each(function($item) use (&$gcm_ids){
                    $gcm_ids[] = $item->client->gcm_id;
                })->delete();
                    
            // cencello il servizio
            $s->db->delete();

            // invio un messaggio a tutti i client che erano collegati a questo servizio
            $jsonMessage = json_encode(array(
                "command" => "service.ee.destroy",
                "service" => $service->id,
            ));
            $message = array( "data" => $jsonMessage );
            
            $gcm = new GCM();
            $gcm->send_notification($gcm_ids, $message);         
            
            // restituisco l'id del servizio appena cancellato
            $output->data = $s->db->get(); 

        break;
    

    //-----------------------------------------------

    // seleziona tutti gli oggetti dal db
        case "select":
            $model = new $subject();
            $c = $model->getCollection();
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
            $model->db->set($input);
            $output->data = $model->db->getCollection()->similar()->getArray();            
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
            $model->db->delete();
            $output->data = $model->db->get(); 
        break;

    }

} catch (Exception $ex) {
    $output->type = "error";
    $output->err = $ex->getMessage();
} 



end_session_helper();