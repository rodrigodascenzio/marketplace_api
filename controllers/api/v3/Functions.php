<?php
namespace Controller\api\v3;

use \Firebase\JWT\JWT;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\Ses\SesClient;
use Aws\Sns\SnsClient; 
use Aws\Exception\AwsException;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

/**
 * Controller de Exemplo
 */

class Functions {
    /**
     * Container - Ele recebe uma instancia de um 
     * container da rota no construtor
     * @var object s
     */
   protected $app;
   
   /**
    * Método Construtor
    * @param ContainerInterface $container
    */
   public function __construct($container) {
       $this->app = $container;
   }


function getSchedulingCompany($request,$response,$args){
    $token = $request->getAttribute("token");
    if (!($args['companyId'] == $token["comp"] || $args['companyId'] == $token["emp_comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $timezone = $this->get_nearest_timezone($args['latitude'],$args['longitude'],$args['countryCode']);
    date_default_timezone_set($timezone);

        $sth = $this->app->db->prepare("
            SELECT 
            upper(scheduling.scheduling_id) as scheduling_id,  
            scheduling.user_id,
            scheduling.company_name,
            scheduling.company_id,
            scheduling.status,
            scheduling.source,
            scheduling.service_name, 
            scheduling.employee_name, 
            scheduling.user_name,
            scheduling.subtotal_amount,
            scheduling.payment_method,
            scheduling.discount_amount,
            scheduling.total_amount,
            scheduling.service_duration, 
            date(CONVERT_TZ(scheduling.created_date, 'UTC', :timezone)) as created_date, 
            date(scheduling.start_time)as start_date, 
            scheduling.start_time as start_date_time,
            DATE_FORMAT(scheduling.start_time, '%H:%i') as start_time, 
            DATE_FORMAT(scheduling.end_time, '%H:%i') as end_time,
            scheduling.note, 
            users.rating/users.num_rating as user_rating, 
            users.num_rating as user_num_rating,
            if(TIMESTAMPDIFF(HOUR, scheduling.completed_date, now()) >= 1 or scheduling.source = :pos, 0,1) as is_chat_available
            FROM scheduling
            join users on users.user_id = scheduling.user_id
            WHERE scheduling.company_id = :companyId and scheduling.status = :position order by scheduling.status desc, start_date asc");

        $sth->bindParam(":companyId", $args['companyId']);
        $sth->bindParam(":position", $args['position']);
        $sth->bindValue(":pos", "nuppin_company");
        $sth->bindParam(":timezone", $timezone);
        $sth->execute();
        $agendamento = $sth->fetchAll();

        $data = date('Y-m-d H:i:s');

        foreach ($agendamento as $i => $item) {
            if (strtotime($data) < strtotime($agendamento[$i]['start_date_time'])) {
                $agendamento[$i]['expired'] = false;
            }else{
                $agendamento[$i]['expired'] = true;
            }
        }

        $arrayJson = array(
        "scheduling" => $agendamento
        );
        return $response->withJson($arrayJson);
}

function getLegal($request,$response,$args){
    $sth = $this->app->db->prepare("SELECT legal.content from legal where type = :type");
    $sth ->bindValue(":type",$args['type']);
    $sth->execute();
    $termos = $sth->fetchObject();

    $this->houston("Alguem tá lendo o termo de uso 😨");
    return $response->withJson($termos);
}

function addTempNotification($request,$response,$args){
    $user = $request->getParsedBody();
    $this->notificationTempToken($user['token'], "nuppin");
}

function addTempNotificationCompany($request,$response,$args){
    $user = $request->getParsedBody();
    $this->notificationTempToken($user['token'], "nuppin_company");
}

function addUserNotification($request,$response,$args){
    $user = $request->getParsedBody();
    $token = $request->getAttribute("token");
    if (!($user['user_id'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    try{
        $this->notificationRegister($user['token'],$user['user_id'], "nuppin");
    }catch(\Throwable $e){
        return $response->withJson(false)->withStatus(500);
    }    
}

function addUserNotificationCompany($request,$response,$args){
    $user = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($user['user_id'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    try{
        $this->notificationRegister($user['token'],$user['user_id'], "nuppin_company");
    }catch(\Throwable $e){
        return $response->withJson(false)->withStatus(500);
    }
}

function deleteAddress($request,$response,$args){
    $address = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($address['user_id'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sql="DELETE from address where address.address_id = :address and address.user_id = :id";
    $sth = $this->app->db->prepare($sql);
    $sth ->bindValue(":id",$address['user_id']);
    $sth ->bindValue(":address",$address['address_id']);
    $sth->execute();
    return $response->withJson($input);
}

function updateAddress($request,$response,$args){
    $address = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($address['user_id'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sets = [];

    foreach ($address as $key => $VALUES) {
        $sets[] = $key." = :".$key;
    }   

    $this->app->db->beginTransaction();
    try{
        $sth1 = $this->app->db->prepare("UPDATE address SET ".implode(',', $sets)." WHERE address.address_id = :address_id and address.user_id = :user_id");
        foreach ($address as $key => $value) {$sth1->bindValue(':'.$key,$value);}
        $sth1->execute();


        $sth2 = $this->app->db->prepare("UPDATE address SET address.is_selected = :notSelected WHERE address.user_id = :user_id and address.address_id != :address_id");
        $sth2->bindValue(':notSelected',"0");
        foreach ($address as $key => $value) {$sth2->bindValue(':'.$key,$value);}
        $sth2->execute();

        $this->app->db->commit();

        return $response->withJson($address);
    }catch(\Throwable $e){
        $this->app->db->rollBack();
        return $response->withJson(false);
    }
}

function updateAddressToSelected($request,$response,$args){
    $address = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($address['user_id'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $this->app->db->beginTransaction();
    try{

        $sth1 =  $this->app->db->prepare( "UPDATE address SET address.is_selected = :notSelected WHERE address.user_id = :userId and address.address_id != :id");
        $sth1->bindValue(':notSelected',"0");
        $sth1->bindValue(':userId', $address['user_id']);
        $sth1->bindValue(':id', $address['address_id']);
        $sth1->execute();

        $sth2 =  $this->app->db->prepare("UPDATE address SET address.is_selected = :selected WHERE address.user_id = :userId and address.address_id = :id");
        $sth2->bindValue(':selected',"1");
        $sth2->bindValue(':userId', $address['user_id']);
        $sth2->bindValue(':id', $address['address_id']);
        $sth2->execute();

       $this->app->db->commit();

        return $response->withJson($address);

    }catch(\Throwable $e){
        $this->app->db->rollBack();
        return $response->withJson(false);
    }
}

function addUserAddress($request,$response){
    $address = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($address['user_id'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $keys = array_keys($address); 
    $enduId;
    $couponId;

    $this->app->db->beginTransaction();
    try{
      $sql = "SELECT coupon.coupon_id FROM coupon 
            join company on company.company_id = coupon.company_id
            WHERE 
            (case when company.model_type = 'fixed' then (ST_Distance_Sphere(POINT(:longitude, :latitude), POINT(company.longitude, company.latitude))/1000) else ((case when IF(EXISTS(SELECT mobile.company_id FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL),1,0) = 1 then (SELECT ST_Distance_Sphere(POINT(:longitude, :latitude),POINT(mobile.longitude, mobile.latitude))/1000 FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL) else (ST_Distance_Sphere(POINT(:longitude, :latitude), POINT(company.longitude, company.latitude))/1000)end))end) < company.max_radius and NOT EXISTS (SELECT coupon_users.coupon_id FROM coupon_users WHERE coupon_users.coupon_id = coupon.coupon_id and coupon_users.user_id = :userId);";

        $sth = $this->app->db->prepare($sql);
        $sth->bindValue(':latitude',$address['latitude']);
        $sth->bindValue(':longitude',$address['longitude']);
        $sth->bindValue(':userId',$address['user_id']);
        $sth->execute();
        $coupon = $sth->fetchAll();

        if(sizeof($coupon) > 0){
            $sql3 = "INSERT INTO coupon_users (coupon_id, user_id) VALUES(:couponId,:userId)";
            $sth3 = $this->app->db->prepare($sql3);
            foreach ($coupon as $key1 => $value1) {
            foreach ($value1 as $key2 => $value1) {
                $couponId = $value1;
            }
                $sth3 ->bindValue(':userId',$address['user_id']);
                $sth3 ->bindValue(':couponId',$couponId);
                $sth3->execute();
            }
        }
        $sql = "INSERT INTO address (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")";
        $sth = $this->app->db->prepare($sql);
        foreach ($address as $key => $value) {
            if($key == "address_id"){
                $enduId = $this->uniqidReal(9);
                $sth ->bindValue(":".$key,$enduId);
            }else{
                $sth ->bindValue(':'.$key,$value);
            }
        }
        $sth->execute();
        $address['address_id'] = $enduId;

        $sth2 =  $this->app->db->prepare("UPDATE address SET address.is_selected = :notSelected WHERE address.user_id = :id and address.address_id != :lastId");
        $sth2 ->bindValue(':notSelected',"0");
        $sth2 ->bindValue(':id', $address['user_id']);
        $sth2 ->bindValue(':lastId', $address['address_id']);
        $sth2 ->execute();

        $this->app->db->commit();
        $this->mrGoodNews("Endereço adicionado 🌎");

        return $response->withJson($address);
    }catch(\Throwable $e){
        $this->app->db->rollBack();
        return $response->withJson(false);
    }
}

function getAdressUser($request,$response,$args){
    $token = $request->getAttribute("token");

    if (!($args['userId'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("SELECT * FROM address  where address.user_id = :userId order by address.is_selected desc");
    $sth->bindParam(":userId", $args['userId']);
    $sth->execute();
    $address = $sth->fetchAll();
    return $response->withJson($address);
}

function getCompanyOrderByStatus($request, $response, $args){
    $token = $request->getAttribute("token");

    if (!($args['companyId'] == $token["comp"] || $args['companyId'] == $token["emp_comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare(
        "SELECT orders.*,
        if(TIMESTAMPDIFF(HOUR, orders.completed_date, now()) >= 1 or orders.source = :pos, 0,1) as is_chat_available,
        users.rating/users.num_rating as user_rating, 
        users.num_rating as user_num_rating
        from orders 
        join users on users.user_id = orders.user_id
        WHERE orders.company_id = :companyId and orders.status = :position order by orders.status desc, orders.created_date asc;

        SELECT 
        orders.order_id,
        item.order_id,
        item.name,
        item.size_name,
        item.order_item_id,
        item.quantity,
        item.total_amount,
        item.note    
        from orders
        join order_item as item on item.order_id = orders.order_id
        WHERE orders.company_id = :companyId and orders.status = :position
        order by item.quantity;

        SELECT extra.* FROM orders
        JOIN order_item_extra as extra on extra.order_id = orders.order_id
        WHERE orders.company_id = :companyId and orders.status = :position 
        "
    );
    $sth->bindParam(":companyId", $args['companyId']);
    $sth->bindParam(":position", $args['position']);
    $sth->bindValue(":pos", "nuppin_company");
    $sth->execute();
    $order = $sth->fetchAll();
    $sth->nextRowset();
    $product = $sth->fetchAll(\PDO::FETCH_GROUP);
    $sth->nextRowset();
    $extras = $sth->fetchAll();


    //todo -- Alterar em todos os outros lugares esses tipos onde usava uma array auxiliar, para mudar direto na referência do array usando o &
    foreach ($order as &$o) {
        foreach ($product as $key => &$p) {
            if ($o['order_id'] == $key) {   
                if (sizeof($extras)>0) {
                        foreach ($p as &$p1) {
                            foreach ($extras as $e) {
                                if ($p1["order_item_id"] == $e["order_item_id"]) {
                                    $p1["extra"][] = $e;
                                }
                            }
                        }
                    }
                $o['order_item'] = $p;
            }
        }
    }

    $arrayJson = array(
        "order" => $order
    );
    return $response->withJson($arrayJson);
}

function getOrderDetailUser($request,$response,$args){
    $token = $request->getAttribute("token");

    if (!($args['userId'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    //todo - colocar o convert_tz no date para retornara data com o fuso certo
    $timezone = $this->get_nearest_timezone($args['latitude'],$args['longitude'],$args['countryCode']);
    date_default_timezone_set($timezone);
    $sth = $this->app->db->prepare(
        "SELECT 
        orders.order_id,
        orders.company_id,
        orders.company_name,
        orders.user_id,
        orders.delivery_amount,
        orders.note,
        orders.status,
        orders.payment_method,
        date(CONVERT_TZ(orders.created_date, 'UTC', :timezone)) as created_date, 
        orders.total_amount,
        orders.discount_amount,
        orders.subtotal_amount,
        orders.rating,
        orders.type,
        orders.coupon_id,
        if(TIMESTAMPDIFF(HOUR, orders.completed_date, now()) >= 1, 0,1) as is_chat_available,
        company.category_company_id,
        company.photo,
        orders.address,
        orders.latitude,
        orders.longitude
        from orders
        join company on company.company_id = orders.company_id
        where orders.user_id = :userId and orders.order_id = :orderId
        order by orders.created_date desc;

        SELECT 
        orders.order_id, 
        item.order_item_id,
        item.name,
        item.size_name,
        item.product_id,
        item.quantity,
        item.total_amount,
        item.note
        from orders
        join order_item as item on item.order_id = orders.order_id
        where orders.user_id = :userId and orders.order_id = :orderId
        order by item.quantity;

        SELECT chat.chat_id from chat where chat.seen_date is null and chat.order_id = :orderId and chat.chat_from != :userId;

        SELECT * FROM order_item_extra as extra where extra.order_id = :orderId;        
        "
    );
    $sth->bindParam(":userId", $args['userId']);
    $sth->bindParam(":orderId", $args['orderId']);
    $sth->bindValue(":timezone", $timezone);

    $sth->execute();
    $order = $sth->fetchObject();
    $sth->nextRowset();
    $product = $sth->fetchAll();
    $sth->nextRowset();
    $chat = $sth->fetchAll();
    $sth->nextRowset();
    $extras = $sth->fetchAll();

    if($order != false){
        if (sizeof($extras)>0) {
            $productExtras = array();
            foreach ($product as $p) {
                foreach ($extras as $e) {
                    if ($p["order_item_id"] == $e["order_item_id"]) {
                        $p["extra"][] = $e;
                    }
                }
                array_push($productExtras, $p);
            }
            $order->item = $productExtras;
        }else{
            $order->item = $product;
        }
    }
    
    $arrayJson = array(
        "order" => $order,
        "chat" => $chat
    );

    return $response->withJson($arrayJson);
}


function getOrderUser($request,$response,$args){
    $token = $request->getAttribute("token");

    if (!($args['userId'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $timezone = $this->get_nearest_timezone($args['latitude'],$args['longitude'],$args['countryCode']);
    date_default_timezone_set($timezone);

    if ($args['position'] === "3") {
        $sql = "SELECT 
        scheduling.scheduling_id,
        scheduling.company_id,
        scheduling.company_name,
        scheduling.user_id,
        scheduling.status,
        scheduling.rating,
        scheduling.type,
        date(CONVERT_TZ(scheduling.created_date, 'UTC', :timezone)) as created_date, 
        DATE_FORMAT(scheduling.start_time, '%H:%i') as start_time, 
        DATE_FORMAT(scheduling.end_time, '%H:%i') as end_time,         
        date(scheduling.start_time) as start_date,
        company.photo as photo
        from scheduling
        join company on company.company_id = scheduling.company_id
        where scheduling.user_id = :userId and
        company.category_company_id = :position and scheduling.source = 'nuppin'
        order by scheduling.created_date desc";
    }else{
        $sql = "SELECT 
        orders.order_id,
        orders.company_id,
        orders.company_name,
        orders.user_id,
        orders.delivery_amount,
        orders.note,
        orders.status,
        orders.payment_method,
        orders.type,
        date(CONVERT_TZ(orders.created_date, 'UTC', :timezone)) as created_date, 
        orders.total_amount,
        orders.rating,
        company.category_company_id,
        company.photo
        from orders
        join company on company.company_id = orders.company_id
        where orders.user_id = :userId and
        company.category_company_id = :position and orders.source = 'nuppin'
        order by orders.created_date desc";
    }
    $sth = $this->app->db->prepare($sql);
    $sth->bindParam(":userId", $args['userId']);
    $sth->bindParam(":position", $args['position']);
    $sth->bindValue(":timezone", $timezone);
    $sth->execute();

    $order = $sth->fetchAll();

    $arrayJson = array(
        "order" => $order
    );

    return $response->withJson($arrayJson);
}

function updateOrderStatus($request,$response,$args){
    $inputOrder = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputOrder['company_id'] == $token["comp"] || $inputOrder['company_id'] == $token["emp_comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sql3 = "SELECT orders.status FROM orders WHERE orders.order_id = :orderId";
    $sth3 = $this->app->db->prepare($sql3);
    $sth3->bindValue(':orderId', $inputOrder['order_id']);
    $sth3->execute();
    $order = $sth3->fetchObject();

    if($order->status == "canceled_user"){
        $error = array();
        $error['error_code'] = "001";
        $error['error_message'] = "Não é possivel aceitar, o cliente já cancelou o pedido!";
        $arrayJson = array("error" => $error);
        return $response->withJson($arrayJson);
    }else if($order->status == $args['idStatus']){
        $error = array();
        $error['error_code'] = "002";
        $error['error_message'] = "O status desse pedido já foi atualizado!";
        $arrayJson = array("error" => $error);
        return $response->withJson($arrayJson);
    }

    $this->app->db->beginTransaction();
    try{

        $sql;
        switch($args['idStatus']){
            case "accepted":
              $sql = "UPDATE orders SET orders.status = :status, orders.accepted_date = now() WHERE orders.order_id = :orderId and orders.user_id = :userId and orders.company_id = :companyId";
            break;
            case "delivery":
            case "released":
            $sql = "UPDATE orders SET orders.status = :status, orders.released_date = now() WHERE orders.order_id = :orderId and orders.user_id = :userId and orders.company_id = :companyId";
            break;
            case "concluded_not_rated":
             if ($inputOrder['source'] == 'nuppin_company') {
                $args['idStatus'] = "concluded";
                $this->mrGoodNews("Pedido manual concluido 😍");
            }else{
                $this->mrGoodNews("Pedido concluido 😍");
            }
            case "concluded":
            case "canceled_refused":
               $sql = "UPDATE orders SET orders.status = :status, orders.completed_date = now() WHERE orders.order_id = :orderId and orders.user_id = :userId and orders.company_id = :companyId";
            break;
        }

        $sth = $this->app->db->prepare($sql);
        $sth->bindValue(':status',$args['idStatus']);
        $sth->bindValue(':orderId', $inputOrder['order_id']);
        $sth->bindValue(':userId', $inputOrder['user_id']);
        $sth->bindValue(':companyId', $inputOrder['company_id']);
        $sth->execute();


        if ($sth->rowCount()>0) {
            if (!($inputOrder['source'] == "nuppin_company")) {
                switch ($args['idStatus']){
                    //cancelado pelo comercio
                    case "canceled_refused":
                        $this->incrementProductStock($inputOrder['order_id']);

                        $this->send_notification_cancel_order($inputOrder['user_id'],"Pedido: ".strtoupper($inputOrder['order_id'])." foi cancelado pelo estabelecimento", "Pedido cancelado 😟", $this->app->db, "user");
                        $this->houston("Pedido não aceito pela loja 😟");
                    break;
                    case "accepted":
                        $this->send_notification($inputOrder['user_id'],"Seu pedido foi aceito pelo estabelecimento", "Pedido aceito 😃", $this->app->db, "user");
                    break;
                    case "delivery":
                        $this->send_notification($inputOrder['user_id'],"Seu pedido já está a caminho", "Saiu pra entrega 🛵", $this->app->db, "user");
                    break;
                    case "released":
                        $this->send_notification($inputOrder['user_id'],"Pedido liberado para retirada", "Já pode retirar 🛒", $this->app->db, "user");
                    break;
                    case "concluded_not_rated":
                        $this->send_notification($inputOrder['user_id'],"Pedido concluido com sucesso", "Concluido 😍", $this->app->db, "user");
                    break;
                    case "concluded":
                        $this->UserRating($inputOrder['user_id'],$args['rating']); 
                    break;
                }
            }
            $this->app->db->commit();
            return $response->withJson(true);
        }else{
            return $response->withJson(false);
        }
    }catch(\Throwable $e){
       $this->app->db->rollBack();
        return $response->withJson(false)->withStatus(500);
    }
}

function updateOrderStatusFromUser($request,$response,$args){
    $inputOrder = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputOrder['user_id'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    if ($args['idStatus'] == "canceled_user") {
        $sql3 = "SELECT orders.status FROM orders WHERE orders.order_id = :orderId";
        $sth3 = $this->app->db->prepare($sql3);
        $sth3->bindValue(':orderId', $inputOrder['order_id']);
        $sth3->execute();
        $order = $sth3->fetchObject();
        if($order->status != "pending"){
            $error = array();
            $error['error_code'] = "001";
            $error['error_message'] = "Não é possivel cancelar, o pedido já foi aceito pelo estabelecimento";
            $arrayJson = array("error" => $error);
            return $response->withJson($arrayJson);
        }
    }

    $this->app->db->beginTransaction();
    try{

        $sql;
        if($args['idStatus'] == "canceled_user"){
            $sql = "UPDATE orders SET orders.status = :status, orders.completed_date = now() WHERE orders.order_id = :orderId and orders.user_id = :userId";
        }

        $sth = $this->app->db->prepare($sql);
        $sth->bindValue(':status',$args['idStatus']);
        $sth->bindValue(':orderId', $inputOrder['order_id']);
        $sth->bindValue(':userId', $inputOrder['user_id']);
        $sth->execute();

        if ($sth->rowCount()>0) {      
            if($args['idStatus'] == "canceled_user"){
                //cancelado pelo usuario
                $this->incrementProductStock($inputOrder['order_id']);
                $this->send_notification_cancel_order($inputOrder['company_id'],"Pedido: ".strtoupper($inputOrder['order_id'])." foi cancelado", "Pedido cancelado", $this->app->db, "all_company");
                $this->houston("Pedido cancelado pelo usuario 😟");
            }
            $this->app->db->commit();
            return $response->withJson(true);
        }else{
            $this->app->db->rollBack();
            return $response->withJson(false);
        }
    }catch(\Throwable $e){
       $this->app->db->rollBack();
        return $response->withJson(false);
    }
}


function getProductDetail($request,$response,$args){
    //todo token

    $sth= $this->app->db->prepare(
        "SELECT * FROM product WHERE product.product_id = :productId;

          SELECT collection.*, pc.product_id
            FROM collection
            join product_collection as pc on pc.product_id = :productId AND pc.collection_id = collection.collection_id
            where (select count(*) from collection_extra as ce where ce.collection_id = collection.collection_id) >= collection.min_quantity
            order by pc.position;

            SELECT ce.collection_id, extra.*
            FROM collection_extra as ce
            join extra on extra.extra_id = ce.extra_id
            WHERE extra.extra_id = ce.extra_id and exists (select pc.collection_id from product_collection AS pc WHERE pc.product_id = :productId AND pc.collection_id = ce.collection_id);
        
            SELECT size.* from size
            join product on product.product_id = :productId
            where size.product_id = :productId and product.is_multi_stock = 1 order by size.name");

    $sth ->bindValue(':productId',$args['productId']);
    $sth->execute();
    $product = $sth->fetchObject();
    $sth->nextRowset();
    $collection = $sth->fetchAll();
    $sth->nextRowset();
    $extras = $sth->fetchAll(\PDO::FETCH_GROUP);
    $sth->nextRowset();
    $sizes = $sth->fetchAll();

    foreach ($collection as &$c) {
        foreach ($extras as $key => $e) {
            if ($c['collection_id'] == $key) {
                $c['extra'] = $e;
            }
        }
    }

    $arrayJson = array(
        "product" => $product,
        "collection" => $collection,
        "size" => $sizes
    );
    return  $response->withJson($arrayJson);
}

function getProductAndVerifyCart($request,$response,$args){
    $token = $request->getAttribute("token");

    if (!($args['userId'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sql = "
    SELECT product.*, cart.quantity as cart_quantity, cart.note as cart_note FROM product
    join cart on cart.product_id = :item AND cart.user_id = :userId and cart.cart_id = :cartId 
    WHERE product.product_id = :item;
    
    SELECT c.*, pc.product_id,
    (SELECT sum(ce.quantity) FROM cart_extra as ce where ce.user_id = :userId and ce.product_id = :item and ce.collection_id = c.collection_id and ce.cart_id = :cartId) as quantity_selected,
    (SELECT sum(ce.quantity * extra.price) FROM cart_extra as ce join extra on extra.extra_id = ce.extra_id where ce.user_id = :userId and ce.collection_id = c.collection_id and ce.cart_id = :cartId) as total_price
    FROM collection AS c 
    join product_collection as pc on pc.product_id = :item AND pc.collection_id = c.collection_id
    where (select count(*) from collection_extra as ce where ce.collection_id = c.collection_id) >= c.min_quantity
    order by pc.position;

    SELECT ce.collection_id, extra.*, (SELECT cart_extra.quantity FROM cart_extra where cart_extra.user_id = :userId and cart_extra.product_id = :item and cart_extra.collection_id = ce.collection_id and cart_extra.extra_id = extra.extra_id and cart_extra.cart_id = :cartId) as quantity
    FROM collection_extra as ce
    join extra on extra.extra_id = ce.extra_id
    WHERE exists (select pc.collection_id from product_collection as pc WHERE pc.product_id = :item AND pc.collection_id = ce.collection_id);

    SELECT size.*, (SELECT if(cart.size_id is not null,1,0) FROM cart where cart.user_id = :userId and cart.product_id = size.product_id and cart.cart_id = :cartId and cart.size_id = size.size_id) as is_selected from size
    join product on product.product_id = :item
    where size.product_id = :item and product.is_multi_stock = 1 order by size.name";


    $sth = $this->app->db->prepare($sql);
    $sth->bindValue(':item',$args['item']);
    $sth->bindValue(':userId',$args['userId']);
    $sth->bindValue(':cartId',$args['cartId']);
    $sth->execute();
    $product = $sth->fetchObject();
    $sth->nextRowset();
    $collection = $sth->fetchAll();
    $sth->nextRowset();
    $extras = $sth->fetchAll(\PDO::FETCH_GROUP);
    $sth->nextRowset();
    $sizes = $sth->fetchAll();

    foreach ($collection as &$c) {
        foreach ($extras as $key => $e) {
            if ($c['collection_id'] == $key) {
                $c['extra'] = $e;
            }
        }
    }
    
    $arrayJson = array(
        "product" => $product,
        "collection" => $collection,
        "size" => $sizes
    );
    return  $response->withJson($arrayJson);
}

function updateQtdItemCart($request,$response,$args){
    $inputCart = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputCart['user_id'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $this->app->db->beginTransaction();
    try{

        $sql = "UPDATE cart SET cart.quantity = :qtd, cart.note = :obs, cart.size_id = :sizeId, cart.size_name = :sizeName WHERE cart.cart_id = :cartId and cart.product_id = :item and cart.user_id = :id";
        $sth = $this->app->db->prepare($sql);
        $sth->bindValue(':item',$inputCart['product_id']);
        $sth->bindValue(':qtd', $inputCart['quantity']);
        $sth->bindValue(':obs', $inputCart['note']);
        $sth->bindValue(':id', $inputCart['user_id']);
        $sth->bindValue(':cartId', $inputCart['cart_id']);
        $sth->bindValue(':sizeId', $inputCart['size_id']);
        $sth->bindValue(':sizeName', $inputCart['size_name']);
        $sth->execute();


        $sth2 = $this->app->db->prepare("DELETE from cart_extra where cart_extra.cart_id = :cartId and cart_extra.product_id = :item and cart_extra.user_id = :id");
        $sth2->bindValue(":id",$inputCart['user_id']);
        $sth2->bindValue(":item",$inputCart['product_id']);
        $sth2->bindValue(':cartId', $inputCart['cart_id']);
        $sth2->execute();

        if (sizeof($inputCart['extra']) > 0) {
            $keys = array_keys($inputCart['extra'][0]); 

            foreach($inputCart['extra'] as $key => $value){
                $sth3 = $this->app->db->prepare("INSERT into cart_extra(".implode(',', $keys).") values (:".implode(",:", $keys).")");
                foreach ($value as $key1 => $value1) {
                    if ($key1 == "cart_id") {
                            $sth3->bindValue(':'.$key1, $inputCart['cart_id']);
                    }else if($key1 == "cart_extra_id"){
                        $sth3->bindValue(":".$key1, $this->uniqidReal(9));
                    }else{
                        $sth3->bindValue(':'.$key1,$value1);
                    }   
                }
                $sth3->execute();
            }
        }
        $this->app->db->commit();        
        return $response->withJson($inputCart);
    }catch(\Throwable $e){
        $this->app->db->rollBack();
        return $response->withJson(false);
    }
}

function deleteItemCart($request,$response,$args){
    $inputCart = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputCart['user_id'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $this->app->db->beginTransaction();
    try{

        $sth1 = $this->app->db->prepare("DELETE from cart where cart.cart_id = :cartId and cart.product_id = :item and cart.user_id = :id");
        $sth1->bindValue(":id",$inputCart['user_id']);
        $sth1->bindValue(":item",$inputCart['product_id']);
        $sth1->bindValue(":cartId",$inputCart['cart_id']);
        $sth1->execute();

        if($inputCart["source"] == "nuppin"){
            $sth = $this->app->db->prepare("SELECT * FROM cart WHERE cart.user_id = :userId and cart.source = 'nuppin'");
            $sth->bindValue(':userId', $inputCart["user_id"]);
            $sth->execute();
            $info = $sth->fetchAll();
            if(sizeof($info) < 1){
                $sth2 = $this->app->db->prepare("DELETE from cart_info WHERE cart_info.user_id = :userId");
                $sth2->bindValue(':userId', $inputCart["user_id"]);
                $sth2->execute();
            }
        }

        $this->app->db->commit();        
        return $response->withJson($inputCart);
    }catch(\Throwable $e){
        $this->app->db->rollBack();
        return $response->withJson(false);
    }
  
}

function deleteAllItemsUserCart($request,$response,$args){
    $inputCart = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputCart['user_id'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }
    
    $this->app->db->beginTransaction();
    try{

        $sth1 = $this->app->db->prepare("DELETE from cart where cart.user_id = :id");
        $sth1->bindValue(":id",$inputCart['user_id']);
        $sth1->execute();

        if($inputCart["source"] == "nuppin"){
            $sth2 = $this->app->db->prepare("DELETE from cart_info as info where info.user_id = :id");
            $sth2->bindValue(":id",$inputCart['user_id']);
            $sth2->execute();
        }

        $this->app->db->commit();        
        return $response->withJson($inputCart);
    }catch(\Throwable $e){
        $this->app->db->rollBack();
        return $response->withJson(false);
    } 
}



//========================== FUNCTIONS USER ==============

function getUser($request, $response, $args){
    $token = $request->getAttribute("token");

    if (!($args['userId'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("SELECT users.* FROM users WHERE users.user_id = :userId");
    $sth->bindParam("userId", $args['userId']);
    $sth->execute();
    $user = $sth->fetchObject();
    return $response->withJson($user);
}

function addUser($request, $response){
    $inputUser = $request->getParsedBody();
    $keys = array_keys($inputUser); 
    $userId;

    $this->app->db->beginTransaction();
    try{
        $sql = "INSERT INTO users (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")";
        $sth = $this->app->db->prepare($sql);
        foreach ($inputUser as $key => $value) {
            if($key == "user_id"){
                $userId = $this->uniqidReal(9);
                $sth ->bindValue(":".$key, $userId);
            }else{
                $sth ->bindValue(':'.$key,$value);
            }
        }
        $sth->execute();
        $inputUser['user_id'] = $userId;
        $refreshToken = $this->addRefreshToken($inputUser["user_id"]);
        $inputUser['refresh_token'] = $refreshToken;
        $this->app->db->commit();     
        $this->mrGoodNews("Temos um novo usuário 🥳");
        return $response->withJson($inputUser);
    }catch(\Throwable $e){
        $this->app->db->rollBack();
        return $response->withJson(false)->withStatus(500);
    }
}

function refreshAccessToken($request, $response){
    $inputUser = $request->getParsedBody();
    $token = $request->getAttribute("token");
    $jwt = $request->getAttribute("jwt");
        //todo new here

    if (!($inputUser['user_id'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    try{
        $sth = $this->app->db->prepare("
        SELECT tu.*, owner.company_id, employee.company_id as employee_company, employee.employee_id FROM token as tu 
        left join employee as owner on owner.user_id = tu.user_id and owner.status = 'active' and owner.is_selected = 1 and (owner.role = 'owner' or owner.role = 'admin')
        left join employee on employee.user_id = tu.user_id and employee.status = 'active' and employee.role = 'employee' and employee.is_selected = 1
        WHERE tu.user_id = :userId and tu.token_id = :tokenId");

        $sth->bindValue(':userId',$token['id']);
        $sth->bindValue(':tokenId',$jwt);
        $sth->execute();
        $user = $sth->fetchObject();

        if ($user) {
            $accessTtoken = $this->newAccessToken($user->user_id, $user->company_id, $user->employee_company, $user->employee_id, $jwt, $inputUser["api_version"], $inputUser["aplication_version"], $inputUser["device_type"]);        
            return $response->withHeader("token", $accessTtoken);
        }else{
            return $response->withJson(false)->withStatus(403);
        }
    }catch(\Throwable $e){
        return $response->withJson(false)->withStatus(500);
    }
}

function newAccessToken($userId, $companyId, $employeeCompany, $employeeId, $jwt, $apiVersion, $aplicationVersion, $deviceType) {
    $settings = $this->app->get('settings'); // get settings array.
    $secret = $settings['jwt']['secret'];
    // date: now
    $now = time();
    // date: now 30 minutes
    $future = (time()+(2*60*60));
    $token = array(
        'id' => $userId, // User id
        'comp' => $companyId, // companyId
        'emp' => $employeeId, // employee_id
        'emp_comp' => $employeeCompany, // employee_company
        'iat' => $now, // Start time of the token
        'exp' => $future, // Time the token expires (30 minutes)
    );

    $sth = $this->app->db->prepare("UPDATE token SET token.refresh_date = now(), token.api_version = :apiVersion, token.aplication_version = :aplicationVersion, token.device_type = :deviceType WHERE token.token_id = :jwt");
    $sth->bindValue(':jwt',$jwt);
    $sth->bindValue(':apiVersion',$apiVersion);
    $sth->bindValue(':aplicationVersion',$aplicationVersion);
    $sth->bindValue(':deviceType',$deviceType);
    $sth->execute();

    // Encode Jwt Authentication Token
    return JWT::encode($token, $secret, "HS256");
}

function addRefreshToken($userId) {
    $settings = $this->app->get('settings'); // get settings array.
    $secret = $settings['jwt']['secret'];
    // date: now
    $now = time();
    // date: now + 1 year
    $future = time()+(365*24*60*60);
    $token = array(
        'id' => $userId, // User id
        'iat' => $now, // Start time of the token
        'exp' => $future, // Time the token expires (1 year)
    );
    // Encode Jwt Authentication Token'
    $token = JWT::encode($token, $secret, "HS256");

    try{
        //adicionar esse token no banco de dados
        $sth3 = $this->app->db->prepare("INSERT into token (token_id, user_id) values (:tokenId, :userId)");
        $sth3->bindValue(':tokenId',$token);
        $sth3->bindValue(':userId',$userId);
        $sth3->execute();

        return $token;
    }catch(\Throwable $e){
        throw new \Exception('error. token not generated');
    }
}

function deleteUser($request, $response, $args){
    $inputUser = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputUser['user_id'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("DELETE FROM users WHERE user_id = :userId");
    $sth->bindParam("userId", $inputUser["user_id"]);
    $user =$sth->execute();
    return $response->withJson(true);
}

function logoutUser($request, $response, $args){
    $inputUser = $request->getParsedBody();
    $token = $request->getAttribute("token");
    if (!($inputUser['user_id'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

     $this->app->db->beginTransaction();
    try{
        $sth = $this->app->db->prepare("DELETE FROM token WHERE token_id = :token");
        $sth->bindParam(":token", $inputUser["refresh_"]);
        $sth->execute();

        $sth2 = $this->app->db->prepare("DELETE FROM notification WHERE refresh_token = :token");
        $sth2->bindParam(":token", $inputUser["refresh_token"]);
        $sth2->execute();

        $this->app->db->commit();        
        return $response->withJson(true);

    }catch(\Throwable $e){
        $this->app->db->rollBack();
        return $response->withJson(false)->withStatus(500);
    }
}

function updateUser($request, $response, $args){
    $inputUser = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputUser['user_id'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sets = [];
    foreach ($inputUser as $key => $VALUES) {
        $sets[] = $key." = :".$key;
    }   

    $sql1 = "
        SELECT * FROM users WHERE users.email = :email;
        SELECT * FROM users WHERE users.document_number = :cpf
        ";
    $sth1 = $this->app->db->prepare($sql1);
    $sth1 ->bindValue(':email',$inputUser['email']);
    $sth1 ->bindValue(':cpf',$inputUser['document_number']);
    $sth1->execute();
    $user1 = $sth1->fetchObject();
    $sth1->nextRowset();
    $userCpf = $sth1->fetchObject();

    if(!$user1 && !$userCpf){
        $sql = "UPDATE users SET ".implode(',', $sets)." WHERE users.user_id = :userId";
        $sth = $this->app->db->prepare($sql);
        $sth ->bindValue(':userId',$inputUser['user_id']);
        foreach ($inputUser as $key => $value) {
            $sth ->bindValue(':'.$key,$value);
        }
        $sth->execute();
        $arrayJson = array("users" => $inputUser);
        return $response->withJson($arrayJson);
    } else{
        $error = array();
        if ($user1) {
            $error['error_code'] = "001";
            $error['error_message'] = "Email já cadastrado";
        }else{
            $error['error_code'] = "002";
            $error['error_message'] = "CPF já cadastrado";
        }
        $arrayJson = array("error" => $error,"users" => false);
        return $response->withJson($arrayJson);
    }
}

function UserRating($userId, $rating){
    $sql = "UPDATE users SET 
    users.rating = users.rating+:rating, 
    users.num_rating = users.num_rating+:plusOne
    WHERE users.user_id = :userId;
    ";
    $sth = $this->app->db->prepare($sql);
    $sth ->bindValue(':rating',$rating);
    $sth ->bindValue(':plusOne',1);
    $sth ->bindValue(':userId',$userId);
    $sth ->execute();   
}

//===================================== FUNCTIONS STORES ============================================


//metodo atualizado
function updateCompanyVisibility($request, $response){
    $inputCompany = $request->getParsedBody();
    $token = $request->getAttribute("token");
    if (!($inputCompany['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    if($inputCompany['visibility'] == 1){

            $sql1 = "SELECT company.photo, company.banner_photo FROM company WHERE company.company_id = :companyId";
            $sth1 = $this->app->db->prepare($sql1);
            $sth1->bindValue(':companyId', $inputCompany['company_id']);
            $sth1->execute();
            $company = $sth1->fetchObject();

        if(!$company->photo){
            $error = array();
            $error['error_code'] = "002";
            $error['error_message'] = "Erro. Seu estabelecimento não tem foto de perfil";
            $arrayJson = array("error" => $error);
            return $response->withJson($arrayJson);
        }   
        if(!$company->banner_photo){
            $error = array();
            $error['error_code'] = "003";
            $error['error_message'] = "Erro. Seu estabelecimento não tem foto de capa";
            $arrayJson = array("error" => $error);
            return $response->withJson($arrayJson);
        }   


        if($inputCompany["category_company_id"] == 3){
            $sql3 = "SELECT service.service_id FROM service WHERE service.company_id = :companyId";
            $sth3 = $this->app->db->prepare($sql3);
            $sth3->bindValue(':companyId', $inputCompany['company_id']);
            $sth3->execute();
            $servico = $sth3->fetchAll();
            if(sizeof($servico) < 1){
                    $error = array();
                    $error['error_code'] = "001";
                    $error['error_message'] = "Erro. Seu estabelecimento não tem serviços cadastrados";
                    $arrayJson = array("error" => $error);
                    return $response->withJson($arrayJson);
            }

        }else if($inputCompany["category_company_id"] != 3){
            $sql3 = "SELECT product.product_id FROM product WHERE product.company_id = :companyId";
            $sth3 = $this->app->db->prepare($sql3);
            $sth3->bindValue(':companyId', $inputCompany['company_id']);
            $sth3->execute();
            $servico = $sth3->fetchAll();
            if(sizeof($servico) < 1){
                    $error = array();
                    $error['error_code'] = "001";
                    $error['error_message'] = "Erro. Seu estabelecimento não tem produtos cadastrados";
                    $arrayJson = array("error" => $error);
                    return $response->withJson($arrayJson);
            }
        }

        $this->app->db->beginTransaction();
        try{

            $sth2 = $this->app->db->prepare("DELETE FROM validation where validation.company_id = :companyId");
            $sth2->bindValue(':companyId',$inputCompany['company_id']);
            $sth2->execute();
       
            $sth3 = $this->app->db->prepare("INSERT into validation (validation_id, company_id) values(:validationId, :companyId)");
            $sth3->bindValue(':validationId', $this->uniqidReal(9));
            $sth3->bindValue(':companyId',$inputCompany['company_id']);
            $sth3->execute();

            $this->mrGoodNews("Empresa entrou em análise ✔");

            $this->app->db->commit(); 

            return $response->withJson(2);

        }catch(\Throwable $e){
            $this->app->db->rollBack();
            return $response->withJson(false)->withStatus(500);
        }

    
    }else{
        $this->mrGoodNews("Empresa indisponível ❌");
        $sql = "UPDATE company set company.visibility = :companyVisibility WHERE company.company_id = :companyId;";
        $sth = $this->app->db->prepare($sql);
        $sth->bindValue(':companyId',$inputCompany['company_id']);
        $sth->bindValue(':companyVisibility',$inputCompany['visibility']);
        $sth->execute();
        return $response->withJson($sth->rowCount());
    }
}

function checkCompanyCPFCNPJ($request, $response){
    $input = $request->getParsedBody();
    $sql = "SELECT * FROM company WHERE company.document_number = :dados";
    $sth = $this->app->db->prepare($sql);
    $sth ->bindValue(':dados',$input['document_number']);
    $sth->execute();
    $company = $sth->fetchObject();
    if ($company) {
        return $response->withJson(true);
    }else{
        return $response->withJson($company);
    }
}

function getCompanyByUserId($request, $response, $args){
    $token = $request->getAttribute("token");
    if (!($args['userId'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }


    //joga o date() do php para o fuso selecionado, mas não o do banco de dados como na função now() por exemplo
    date_default_timezone_set($this->get_nearest_timezone($args['latitude'],$args['longitude'],$args['countryCode']));

    $sql;
    $arrayJson = array();
        //pega a loja
            //todo new here

        $sth2 = $this->app->db->prepare("
        SELECT company.* FROM company
        join employee on employee.company_id = company.company_id
        where employee.user_id = :userId and employee.status = 'active' and employee.is_selected = :selected and (employee.role = 'owner' or employee.role = 'admin');");
        $sth2->bindParam(":userId", $args['userId']);
        $sth2->bindValue(":selected", "1");
        $sth2->execute();
        $company = $sth2->fetchObject();

        if($company){
           if ($company->visibility == 0) {
                $sth = $this->app->db->prepare("SELECT * FROM validation where validation.company_id = :companyId and validation.status != 'accepted' order by validation.created_date desc limit 1");
                $sth->bindParam(":companyId", $company->company_id);
                $sth->execute();
                $validation = $sth->fetchObject();
                if ($validation) {
                    $company->validation = $validation->status;
                }
            }
        }

        $arrayJson['company'] = $company;

        //todo new here

        $sthLA = $this->app->db->prepare("UPDATE company 
                JOIN users ON users.user_id = :userId
                SET company.last_activity = now(), users.company_last_activity = now()
                where company.company_id = :companyId and users.user_id = :userId");
        $sthLA->bindParam(":companyId", $company->company_id);
        $sthLA->bindValue(":userId", $args['userId']); 
        $sthLA->execute();

        //se for da category de produtos ou alimentos
        if($company->category_company_id == 1 || $company->category_company_id == 2){
        $sql = "
        SELECT orders.status AS code, COUNT(orders.status) AS quantity, orders.company_id,
        case orders.status 
            when 'pending' then 1
            when 'accepted' then 2
            when 'delivery' then 3
            when 'released' then 4
            when 'concluded_not_rated' then 5
            when 'canceled_user' then 6
        end as status 
        FROM orders WHERE orders.company_id = :companyId and
        (orders.status not LIKE :cancel and orders.status != 'concluded')
        GROUP BY orders.status order by status;

        SELECT invoice.invoice_id from invoice where invoice.company_id = :companyId and (invoice.status != 'paid' and invoice.status != 'completed' and invoice.status != 'free');

        SELECT chat.chat_id from chat where chat.seen_date is null and chat.chat_to = :companyId;
        ";

        $sth = $this->app->db->prepare($sql);
        $sth->bindParam(":companyId", $company->company_id);
        $sth->bindValue(":cancel", "%cancel%");
        $sth->execute();
        $status = $sth->fetchAll();        
        $sth->nextRowset();
        $invoice = $sth->fetchAll();
        $sth->nextRowset();
        $chat = $sth->fetchAll();
        $arrayJson['status']=$status;
        $arrayJson['invoice']=$invoice;
        $arrayJson['chat']=$chat;

    }
    //se for da category de serviços
    else if($company->category_company_id == 3){
        $sql = "
        SELECT scheduling.status code, COUNT(scheduling.status) AS quantity, scheduling.company_id,
        case scheduling.status 
           when 'pending' then 1
           when 'accepted' then 2
           when 'concluded_not_rated' then 3
           when 'canceled_user' then 4
        end as status
        FROM scheduling WHERE scheduling.company_id = :companyId and 
        (scheduling.status = 'pending' or scheduling.status = 'accepted' or scheduling.status = 'concluded_not_rated')
         GROUP BY scheduling.status order by status;

        SELECT invoice.invoice_id from invoice where invoice.company_id = :companyId and (invoice.status != 'paid' and invoice.status != 'completed' and  invoice.status != 'free');

        SELECT chat.chat_id from chat where chat.seen_date is null and chat.chat_to = :companyId;
        ";

        $sth = $this->app->db->prepare($sql);
        $sth->bindParam(":companyId", $company->company_id);
        $sth->execute();
        $scheduling = $sth->fetchAll();
        $sth->nextRowset();
        $invoice = $sth->fetchAll();
        $sth->nextRowset();
        $chat = $sth->fetchAll();
        $arrayJson['scheduling']=$scheduling;
        $arrayJson['invoice']=$invoice;
        $arrayJson['chat']=$chat;

    }
    //se for do tipo fixo
    if ($company->model_type == 'fixed') {
        $sql = "
        SELECT IF(hours.weekday_id > 0,'1','0') AS is_online FROM opening_hours as hours where hours.company_id = :companyId 
        and (DAYOFWEEK(:data) BETWEEN hours.weekday_id 
        and hours.weekday_end_id or (hours.weekday_id = 7 
        and hours.weekday_end_id = 1 AND (DAYOFWEEK(:data) = 7 OR DAYOFWEEK(:data) = 1))) 
        AND :hora BETWEEN if((hours.weekday_end_id > hours.weekday_id OR (hours.weekday_id = 7 
        and hours.weekday_end_id = 1 AND (DAYOFWEEK(:data) = 7 OR DAYOFWEEK(:data) = 1)))
        and DAYOFWEEK(:data) = hours.weekday_end_id,'00:00',hours.start_time) 
        and if((hours.weekday_end_id > hours.weekday_id OR (hours.weekday_id = 7 
        and hours.weekday_end_id = 1 AND (DAYOFWEEK(:data) = 7 OR DAYOFWEEK(:data) = 1))) 
        and DAYOFWEEK(:data) = hours.weekday_id,'23:59',hours.end_time)
        ";

        $sth = $this->app->db->prepare($sql);
        $sth->bindParam(":companyId", $company->company_id);
        $sth->bindValue(":hora",date("H:i"));
        $sth->bindValue(":data",date("Y-m-d"));
        $sth->execute();
        $onOrOff = $sth->fetchObject();
        if (!$company == false) {
            if ($onOrOff->is_online) {
                $company->is_online = $onOrOff->is_online;
            }else{
                $company->is_online = '0';
            }
        }
    }
    //se for do tipo movel
    else{
        $sql = "SELECT * FROM mobile where mobile.company_id = :companyId and mobile.end_date is null";
        $sth = $this->app->db->prepare($sql);
        $sth->bindParam(":companyId", $company->company_id);
        $sth->execute();
        $onOrOff = $sth->fetchObject();
        if (!$company == false) {
            if ($onOrOff) {
                $company->is_online = '1';
                $arrayJson['mobile']=$onOrOff;
            }else{
                $company->is_online = '0';
            }
        }
    }
    return $response->withJson($arrayJson);
}

function getCompaniesByUserId($request, $response, $args){
    $token = $request->getAttribute("token");
    if (!($args['userId'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

        //todo new here

    $sth = $this->app->db->prepare("
        SELECT 
        company.*,
        cc.name AS category_name,
        sc.name AS subcategory_name
        FROM company
        join employee on employee.company_id = company.company_id
        JOIN category_company as cc ON cc.category_company_id = company.category_company_id
        JOIN subcategory_company as sc ON sc.subcategory_company_id = company.subcategory_company_id
        where employee.user_id = :userId and employee.status = 'active' and employee.is_selected = :selected and (employee.role = 'owner' or employee.role = 'admin');

        SELECT employee.* from employee
        join company on company.company_id = employee.company_id and employee.role = 'employee'
        where employee.user_id = :userId and employee.status = :active;

        select 
        plan.plan_id,
        plan.name,
        plan_company.price,
        plan_company.trial_price,
        truncate(plan_company.fee*100,0),
        truncate(plan_company.trial_fee*100,0),
        plan_company.trial_period,
        (case when TIMESTAMPDIFF(MONTH, plan_company.created_date, now()) < plan_company.trial_period then 'true' else 'false' end) as is_trial
        from plan
        join employee on employee.user_id = :userId and employee.is_selected = :selected and employee.status = 'active' and (employee.role = 'owner' or employee.role = 'admin')
        join plan_company on plan_company.company_id = employee.company_id;
        where plan_id = plan_company.plan_company_id;
        ");
    $sth->bindParam(":userId", $args['userId']);
    $sth->bindValue(":selected", 1);
    $sth->bindValue(":active", 'active');
    $sth->execute();
    $company = $sth->fetchObject();
    $sth->nextRowset();
    $employee = $sth->fetchObject();
    $sth->nextRowset();
    $plan = $sth->fetchObject();

      $arrayJson = array(
            "company" => $company,
            "employee" => $employee,
            "plan" => $plan
        );
    return $response->withJson($arrayJson);
}


function getCompanyByEmployeeId($request, $response, $args){
    $token = $request->getAttribute("token");
    if (!($args['userId'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    //joga o date() do php para o fuso selecionado, mas não o do banco de dados como na função now() por exemplo
    date_default_timezone_set($this->get_nearest_timezone($args['latitude'],$args['longitude'],$args['countryCode']));

    $sql;
    $arrayJson = array();
        //pega a loja
        $sth2 = $this->app->db->prepare("
        SELECT 
        company.*,
        cc.name AS category_name,
        sc.name AS subcategory_name
        FROM company
        join employee on employee.company_id = company.company_id
        JOIN category_company as cc ON cc.category_company_id = company.category_company_id
        JOIN subcategory_company as sc ON sc.subcategory_company_id = company.subcategory_company_id
        where employee.user_id = :userId and employee.status = 'active' and employee.role = 'employee';");
        $sth2->bindParam(":userId", $args['userId']);
        $sth2->execute();
        $company = $sth2->fetchObject();
        $arrayJson['company'] = $company;

        
        $sthLA = $this->app->db->prepare("UPDATE company 
                JOIN users ON users.user_id = company.user_id
                SET company.last_activity = now(), users.company_last_activity = now()
                where company.company_id = :companyId and users.user_id = :userId");
        $sthLA->bindParam(":companyId", $company->company_id);
        $sthLA->bindValue(":userId", $args['userId']); 
        $sthLA->execute();


        //se for da category de produtos ou alimentos
        if($company->category_company_id == 1 || $company->category_company_id == 2){
        $sql = "
        SELECT orders.status AS code, COUNT(orders.status) AS quantity, orders.company_id,
        case orders.status 
            when 'pending' then 1
            when 'accepted' then 2
            when 'delivery' then 3
            when 'released' then 4
            when 'concluded_not_rated' then 5
            when 'canceled_user' then 6
        end as status 
        FROM orders WHERE orders.company_id = :companyId and
        (orders.status NOT LIKE :cancel and orders.status != 'concluded')
        GROUP BY orders.status order by status;

        SELECT chat.chat_id from chat where chat.seen_date is null and chat.chat_to = :companyId;";

        $sth = $this->app->db->prepare($sql);
        $sth->bindParam(":companyId", $company->company_id);
        $sth->bindValue(":cancel", "%cancel%");
        $sth->execute();
        $status = $sth->fetchAll();        
        $sth->nextRowset();
        $chat = $sth->fetchAll();
        $arrayJson['status']=$status;
        $arrayJson['chat']=$chat;

    }
    //se for da category de serviços
    else if($company->category_company_id == 3){
        $sql = "
        SELECT scheduling.status code, COUNT(scheduling.status) AS quantity, scheduling.company_id,
        case scheduling.status 
           when 'pending' then 1
           when 'accepted' then 2
           when 'concluded_not_rated' then 3
           when 'canceled_user' then 4
        end as status
        FROM scheduling WHERE scheduling.company_id = :companyId and 
        (scheduling.status = 'pending' or scheduling.status = 'accepted' or scheduling.status = 'concluded_not_rated')
         GROUP BY scheduling.status order by status;

        SELECT chat.chat_id from chat where chat.seen_date is null and chat.chat_to = :companyId;
        ";

        $sth = $this->app->db->prepare($sql);
        $sth->bindParam(":companyId", $company->company_id);
        $sth->execute();
        $scheduling = $sth->fetchAll();
        $sth->nextRowset();
        $chat = $sth->fetchAll();
        $arrayJson['scheduling']=$scheduling;
        $arrayJson['chat']=$chat;

    }
    //se for do tipo fixo
    if ($company->model_type == 'fixed') {
        $sql = "
        SELECT IF(hours.weekday_id > 0,'1','0') AS is_online FROM opening_hours as hours where hours.company_id = :companyId 
        and (DAYOFWEEK(:data) BETWEEN hours.weekday_id 
        and hours.weekday_end_id or (hours.weekday_id = 7 
        and hours.weekday_end_id = 1 AND (DAYOFWEEK(:data) = 7 OR DAYOFWEEK(:data) = 1))) 
        AND :hora BETWEEN if((hours.weekday_end_id > hours.weekday_id OR (hours.weekday_id = 7 
        and hours.weekday_end_id = 1 AND (DAYOFWEEK(:data) = 7 OR DAYOFWEEK(:data) = 1)))
        and DAYOFWEEK(:data) = hours.weekday_end_id,'00:00',hours.start_time) 
        and if((hours.weekday_end_id > hours.weekday_id OR (hours.weekday_id = 7 
        and hours.weekday_end_id = 1 AND (DAYOFWEEK(:data) = 7 OR DAYOFWEEK(:data) = 1))) 
        and DAYOFWEEK(:data) = hours.weekday_id,'23:59',hours.end_time)
        ";

        $sth = $this->app->db->prepare($sql);
        $sth->bindParam(":companyId", $company->company_id);
        $sth->bindValue(":hora",date("H:i"));
        $sth->bindValue(":data",date("Y-m-d"));
        $sth->execute();
        $onOrOff = $sth->fetchObject();
        if (!$company == false) {
            if ($onOrOff->is_online) {
                $company->is_online = $onOrOff->is_online;
            }else{
                $company->is_online = '0';
            }
        }
    }  //se for do tipo movel
    else{
        $sql = "SELECT * FROM mobile where mobile.company_id = :companyId and mobile.end_date is null";
        $sth = $this->app->db->prepare($sql);
        $sth->bindParam(":companyId", $company->company_id);
        $sth->execute();
        $onOrOff = $sth->fetchObject();
        if (!$company == false) {
            if ($onOrOff) {
                $company->is_online = '1';
                $arrayJson['mobile']=$onOrOff;
            }else{
                $company->is_online = '0';
            }
        }
    }
    return $response->withJson($arrayJson);
}

function getCompanies($request, $response, $args){
    $token = $request->getAttribute("token");
    if (!($args['userId'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }


    $sth2 = $this->app->db->prepare("SELECT * FROM address where address.user_id = :userId and address.is_selected = :selected;");
    $sth2->bindParam(":userId", $args['userId']);
    $sth2->bindValue(":selected", "1"); 
    $sth2->execute();
    $address = $sth2->fetchObject();
    $endjson = json_decode(json_encode($address), True);
    //todo -- change above line

    $sthLA = $this->app->db->prepare("UPDATE users SET users.last_activity = now() where users.user_id = :userId");
    $sthLA->bindValue(":userId", $args['userId']); 
    $sthLA->execute();
    
    date_default_timezone_set($this->get_nearest_timezone($endjson['latitude'],$endjson['longitude'],$endJson['country_code']));

    $sth = $this->app->db->prepare(
        "SELECT
        company.name,
        company.company_id,
        company.max_radius_free,
        company.full_address,
        company.category_company_id,
        company.subcategory_company_id,
        company.latitude,
        company.longitude,
        company.rating,
        company.num_rating,
        company.model_type,
        company.delivery_type_value,
        company.is_delivery,
        company.is_local,
        company.delivery_max_time,
        company.delivery_fixed_fee,
        company.delivery_variable_fee,
        company.max_radius,
        company.photo,
        company.banner_photo,
        company.instagram,
        company.facebook,
        company.site,
        company.description,
        cc.name as category_name,
        sc.name as subcategory_name,
         (case when company.model_type = 'fixed' then
            (
                IF(EXISTS(SELECT hours.company_id FROM opening_hours as hours where hours.company_id = company.company_id 
                and (DAYOFWEEK(:data) BETWEEN hours.weekday_id 
                and hours.weekday_end_id or (hours.weekday_id = 7 
                and hours.weekday_end_id = 1 AND (DAYOFWEEK(:data) = 7 OR DAYOFWEEK(:data) = 1))) 
                AND :hora BETWEEN if((hours.weekday_end_id > hours.weekday_id OR (hours.weekday_id = 7 
                and hours.weekday_end_id = 1 AND (DAYOFWEEK(:data) = 7 OR DAYOFWEEK(:data) = 1)))
                and DAYOFWEEK(:data) = hours.weekday_end_id,'00:00',hours.start_time) 
                and if((hours.weekday_end_id > hours.weekday_id OR (hours.weekday_id = 7 
                and hours.weekday_end_id = 1 AND (DAYOFWEEK(:data) = 7 OR DAYOFWEEK(:data) = 1))) 
                and DAYOFWEEK(:data) = hours.weekday_id,'23:59',hours.end_time)
                and TIMESTAMPDIFF(HOUR, company.last_activity, now()) < 24),1,0)
            )
            else
            (
            IF(EXISTS(SELECT mobile.company_id FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL),1,0)
            )
        end) AS is_online,
        (case when company.model_type = 'fixed' then (ST_Distance_Sphere(POINT(company.longitude, company.latitude), POINT(:lo, :la))/1000) else ((case when IF(EXISTS(SELECT mobile.company_id FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL),1,0) = 1 then (SELECT ST_Distance_Sphere(POINT(mobile.longitude, mobile.latitude), POINT(:lo, :la))/1000 FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL) else (ST_Distance_Sphere(POINT(company.longitude, company.latitude), POINT(:lo, :la))/1000)end))end) as distance
        FROM company
        join category_company as cc on cc.category_company_id = company.category_company_id
        join subcategory_company as sc on sc.subcategory_company_id = company.subcategory_company_id
        where
        company.visibility = 1 and
        company.category_company_id BETWEEN IF(:category = 0,0,:category) AND IF(:category = 0,99,:category) and
        (case when company.model_type = 'fixed' then (ST_Distance_Sphere(POINT(company.longitude, company.latitude), POINT(:lo, :la))/1000) else ((case when IF(EXISTS(SELECT mobile.company_id FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL),1,0) = 1 then (SELECT ST_Distance_Sphere(POINT(mobile.longitude, mobile.latitude), POINT(:lo, :la))/1000 FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL) else (ST_Distance_Sphere(POINT(company.longitude, company.latitude), POINT(:lo, :la))/1000)end))end) < company.max_radius and 
        company.status = :active and (company.is_delivery = :setado or company.is_local = :setado)
        order by is_online desc, distance asc, company.rating/company.num_rating desc;

        SELECT
        IF(cart.user_id = :id,1,0) as has_cart,
        sum(cart.quantity) as quantity,
        (sum((product.price + (SELECT COALESCE(sum(size.price),0) FROM size where size.size_id = cart.size_id) + (SELECT COALESCE(SUM(cart_extra.quantity * extra.price),0) FROM cart_extra join extra on cart_extra.extra_id = extra.extra_id where cart_extra.cart_id = cart.cart_id))* cart.quantity)) total_price,
        product.company_id
        FROM cart
        join product on cart.product_id = product.product_id
        where cart.user_id = :id and cart.source = 'nuppin';

        SELECT
        cc.name as category_name,
        cc.category_company_id,
        IF(:category = category_company_id,1,0) is_selected
        FROM category_company as cc;

        SELECT DISTINCT
        sc.name as subcategory_name,
        sc.subcategory_company_id,
        sc.category_company_id
        FROM company
        join subcategory_company as sc on sc.subcategory_company_id = company.subcategory_company_id
        where
        company.visibility = 1 and
        company.category_company_id BETWEEN IF(:category = 0,0,:category) AND IF(:category = 0,99,:category) and
        (case when company.model_type = 'fixed' then (ST_Distance_Sphere(POINT(company.longitude, company.latitude), POINT(:lo, :la))/1000) else ((case when IF(EXISTS(SELECT mobile.company_id FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL),1,0) = 1 then (SELECT ST_Distance_Sphere(POINT(mobile.longitude, mobile.latitude), POINT(:lo, :la))/1000 FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL) else (ST_Distance_Sphere(POINT(company.longitude, company.latitude), POINT(:lo, :la))/1000)end))end) < company.max_radius and company.status = :active and (company.is_delivery = :setado or company.is_local = :setado) order by subcategory_name;
        ");

    $sth->bindParam(":la", $endjson['latitude']);   
    $sth->bindParam(":lo", $endjson['longitude']);
    $sth->bindParam(":id", $args['userId']);
    $sth->bindValue(":active","active");
    $sth->bindParam(":category", $args['category']);  
    $sth->bindValue(":hora",date("H:i"));
    $sth->bindValue(":data",date("Y-m-d"));  
    $sth->bindValue(":setado",1);
    $sth->execute();
    $company = $sth->fetchAll();
    $sth->nextRowset();
    $cart = $sth->fetchObject();
    $sth->nextRowset();
    $category = $sth->fetchAll();
    $sth->nextRowset();
    $subcategory = $sth->fetchAll();

    $arrayJson = array(
        "company" => $company,
        "cart" => $cart,
        "address" => $address,
        "category" => $category,
        "subcategory" => $subcategory
    );

    return $response->withJson($arrayJson);
}
function getCompaniesByCategory($request, $response, $args){
    $token = $request->getAttribute("token");
    if (!($args['userId'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }


    $sth2 = $this->app->db->prepare("SELECT * FROM address where address.user_id = :id and address.is_selected = :selected;");
    $sth2->bindParam(":id", $args['userId']);
    $sth2->bindValue(":selected", "1"); 
    $sth2->execute();
    $address = $sth2->fetchObject();
    $endjson = json_decode(json_encode($address), True);

    date_default_timezone_set($this->get_nearest_timezone($endjson['address_latitude'],$endjson['address_longitude'],$endJson['address_country_code']));

    $sth = $this->app->db->prepare(
        "SELECT
        company.name,
        company.company_id,
        company.max_radius_free,
        company.full_address,
        company.category_company_id,
        company.subcategory_company_id,
        company.latitude,
        company.longitude,
        company.rating,
        company.num_rating,
        company.model_type,
        company.delivery_type_value,
        company.is_delivery,
        company.is_local,
        company.delivery_max_time,
        company.delivery_fixed_fee,
        company.delivery_variable_fee,
        company.max_radius,
        company.photo,
        company.banner_photo,
        company.instagram,
        company.facebook,
        company.site,
        company.description,
        cc.name as category_name,
        sc.name as subcategory_name,
        (case when company.model_type = 'fixed' then
            (
                IF(EXISTS(SELECT hours.company_id FROM opening_hours as hours where hours.company_id = company.company_id 
                and (DAYOFWEEK(:data) BETWEEN hours.weekday_id 
                and hours.weekday_end_id or (hours.weekday_id = 7 
                and hours.weekday_end_id = 1 AND (DAYOFWEEK(:data) = 7 OR DAYOFWEEK(:data) = 1))) 
                AND :hora BETWEEN if((hours.weekday_end_id > hours.weekday_id OR (hours.weekday_id = 7 
                and hours.weekday_end_id = 1 AND (DAYOFWEEK(:data) = 7 OR DAYOFWEEK(:data) = 1)))
                and DAYOFWEEK(:data) = hours.weekday_end_id,'00:00',hours.start_time) 
                and if((hours.weekday_end_id > hours.weekday_id OR (hours.weekday_id = 7 
                and hours.weekday_end_id = 1 AND (DAYOFWEEK(:data) = 7 OR DAYOFWEEK(:data) = 1))) 
                and DAYOFWEEK(:data) = hours.weekday_id,'23:59',hours.end_time)
                and TIMESTAMPDIFF(HOUR, company.last_activity, now()) < 24),1,0)
            )
            else
            (
            IF(EXISTS(SELECT mobile.company_id FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL),1,0)
            )
        end) AS is_online,
         (case when company.model_type = 'fixed' then (ST_Distance_Sphere(POINT(company.longitude, company.latitude), POINT(:lo, :la))/1000) else ((case when IF(EXISTS(SELECT mobile.company_id FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL),1,0) = 1 then (SELECT ST_Distance_Sphere(POINT(mobile.longitude, mobile.latitude), POINT(:lo, :la))/1000 FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL) else (ST_Distance_Sphere(POINT(company.longitude, company.latitude), POINT(:lo, :la))/1000)end))end) as distance
        FROM company
        join category_company as cc on cc.category_company_id = company.category_company_id
        join subcategory_company as sc on sc.subcategory_company_id = company.subcategory_company_id
        where
        company.visibility = 1 and
        (case when company.model_type = 'fixed' then (ST_Distance_Sphere(POINT(company.longitude, company.latitude), POINT(:lo, :la))/1000) else ((case when IF(EXISTS(SELECT mobile.company_id FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL),1,0) = 1 then (SELECT ST_Distance_Sphere(POINT(mobile.longitude, mobile.latitude), POINT(:lo, :la))/1000 FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL) else (ST_Distance_Sphere(POINT(company.longitude, company.latitude), POINT(:lo, :la))/1000)end))end) < company.max_radius and company.status = :active and sc.subcategory_company_id = :sub and cc.category_company_id = :category and (company.is_delivery = :setado or company.is_local = :setado)
        order by is_online desc, distance asc, company.rating/company.num_rating desc;


        SELECT
        IF(cart.user_id = :id,1,0) as has_cart,
        sum(cart.quantity) as quantity,
        (sum((product.price + (SELECT COALESCE(sum(size.price),0) FROM size where size.size_id = cart.size_id) + (SELECT COALESCE(SUM(cart_extra.quantity * extra.price),0) FROM cart_extra join extra on cart_extra.extra_id = extra.extra_id where cart_extra.cart_id = cart.cart_id))* cart.quantity)) total_price,
        product.company_id
        FROM cart
        join product on cart.product_id = product.product_id
        where cart.user_id = :id and cart.source = 'nuppin';
        ");

    $sth->bindParam(":la", $endjson['latitude']);   
    $sth->bindParam(":lo", $endjson['longitude']);
    $sth->bindParam(":id", $args['userId']);
    $sth->bindParam(":sub", $args['subcategory']);
    $sth->bindParam(":category", $args['category']);
    $sth->bindValue(":hora",date("H:i"));
    $sth->bindValue(":data",date("Y-m-d"));
    $sth->bindValue(":active","active");
    $sth->bindValue(":setado",1);
    $sth->execute();
    $company = $sth->fetchAll();
    $sth->nextRowset();
    $cart = $sth->fetchObject();
    $arrayJson = array(
        "company" => $company,
        "cart" => $cart,
        "address" => $address,
    );
    return $response->withJson($arrayJson);
}

function addCompany($request, $response, $args){
    $inputCompany = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputCompany['user_id'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $inputCompany["status"] = "inactive";

    $keys = [];
    $sets = [];
    $userId;
    $companyId;
    foreach ($inputCompany as $key => $VALUES) {
        if($key == "user_id"){
            $userId = $VALUES;
        }else {
            $keys[] = $key;
            $sets[] = ":".$key;
        }
    }

    $companyId = $this->uniqidReal(9);

    $sql1 = "
    SELECT * FROM company WHERE company.document_number = :document;";
    $sth1 = $this->app->db->prepare($sql1);
    $sth1 ->bindValue(':document',$inputCompany['document_number']);
    $sth1->execute();
    $company = $sth1->fetchObject();
    if($company){
            return $response->withJson($company);
    } else{
        $this->app->db->beginTransaction();
        try{
                $sql = "INSERT INTO company (".implode(',', $keys).") VALUES (".implode(",", $sets).")";
                $sth = $this->app->db->prepare($sql);
                foreach ($inputCompany as $key => $value) {
                    if($key == "company_id"){
                        $sth ->bindValue(":".$key, $companyId);
                    }else if ($key != "user_id"){
                        $sth ->bindValue(':'.$key,$value);
                    }
                }
                $sth->execute();
                $this->addCompanyPlan($args['planId'], $companyId);
                $this->addFirstOwnerCompany($userId, $companyId);
                $this->app->db->commit();        

                $this->mrGoodNews("Usuário criou uma empresa 🥳");

                $inputCompany['company_id'] = $companyId;
                return $response->withJson($inputCompany);
        }catch(\Throwable $e){
            $this->app->db->rollBack();
            return $response->withJson(false)->withStatus(500);
        }
    }
}

function addCompanyPlan($planId, $companyId){
    $sql1 = "
    SELECT * FROM plan WHERE plan.plan_id = :planId";
    $sth1 = $this->app->db->prepare($sql1);
    $sth1 ->bindValue(':planId',$planId);
    $sth1->execute();
    $plan = $sth1->fetchObject();

    $sth = $this->app->db->prepare("INSERT into plan_company (plan_company_id, plan_id, company_id, price, fee, trial_period, trial_price, trial_fee, status) 
    values(:id, :planId, :companyId, :price, :fee, :trial, :trialPrice, :trialFee, :status)");
    $sth->bindValue(':id', $this->uniqidReal(9));
    $sth->bindValue(':planId',$plan->plan_id);
    $sth->bindValue(':companyId',$companyId);
    $sth->bindValue(':price',$plan->price);
    $sth->bindValue(':fee',$plan->fee);
    $sth->bindValue(':trial',$plan->trial_period);
    $sth->bindValue(':trialPrice',$plan->trial_price);
    $sth->bindValue(':trialFee',$plan->trial_fee);
    $sth->bindValue(':status',"active");
    return $sth->execute();
}

function addFirstOwnerCompany($userId, $companyId){
    $sql1 = "
    SELECT users.full_name FROM users WHERE users.user_id = :userId";
    $sth1 = $this->app->db->prepare($sql1);
    $sth1 ->bindValue(':userId',$userId);
    $sth1->execute();
    $users = $sth1->fetchObject();

    $sth = $this->app->db->prepare("INSERT into employee (employee_id, user_id, company_id, status, role, user_name) 
    values(:employeeId, :userId, :companyId, :status, :role, :name)");
    $sth->bindValue(':employeeId', $this->uniqidReal(9));
    $sth->bindValue(':userId',$userId);
    $sth->bindValue(':companyId',$companyId);
    $sth->bindValue(':status',"active");
    $sth->bindValue(':role',"owner");
    $sth->bindValue(':name',$users->full_name);
    return $sth->execute();
}

function deleteCompany($request,$response,$args){
    $inputCompany = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputCompany['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("DELETE FROM company WHERE company.company_id = :companyId");
    $sth->bindParam("companyId", $inputCompany['company_id']);
    $company =$sth->execute();
    return $response->withJson($company);
}

function updateCompany($request,$response,$args){
    $inputCompany = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputCompany['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sets = [];
    foreach ($inputCompany as $key => $VALUES) {
        if($key != "is_selected"){// todo - retirar após correção do bug na versão 1.2.0
            $sets[] = $key." = :".$key;
        }
    }   
    $sql = "UPDATE company SET ".implode(',', $sets)." WHERE company.company_id = :id";
    $sth = $this->app->db->prepare($sql);
    $sth ->bindValue(':id',$inputCompany['company_id']);
    foreach ($inputCompany as $key => $value) {
        if($key != "is_selected"){// todo - retirar após correção do bug na versão 1.2.0
            $sth ->bindValue(':'.$key,$value);
        }
    }
    $sth->execute();

    if (preg_match('/[0-9]{8,}/', preg_replace("/[^0-9]/", "",  $inputCompany['description']))){
        $this->mrGoodNews("==== Descrição suspeita 👀 ====="
            ."\nid: ".$inputCompany['company_id']
            ."\nnome: ".$inputCompany['name']
            ."\ndescription: ".$inputCompany['description']
        );
    }

    return $response->withJson($inputCompany);
}

function OrderRating($request, $response){
    $inputOrder = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputOrder['user_id'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }
        //todo new here

    $sql1 = "
    SELECT company.company_id 
    FROM company 
    join employee on employee.company_id = company.company_id
    where employee.user_id = :userId and employee.status = 'active' and company.company_id = :companyId";
    $sth1 = $this->app->db->prepare($sql1);
    $sth1 ->bindValue(':userId',$inputOrder['user_id']);
    $sth1 ->bindValue(':companyId',$inputOrder['company_id']);
    $sth1->execute();
    $match = $sth1->fetchObject();
    if ($match) {
        $error = array();
        $error['error_code'] = "001";
        $error['error_message'] = "Você não pode avaliar sua propria empresa";
        $arrayJson = array("error" => $error);
        return $response->withJson($arrayJson);
    }

    $sql = "UPDATE orders, company 
    SET
    orders.rating = :rating,
    orders.rating_note = :rating_obs, 
    company.rating = company.rating+:rating, 
    company.num_rating = company.num_rating+:plusOne
    WHERE company.company_id = :companyId AND orders.order_id = :orderId and orders.rating < 1;
    ";
    $sth = $this->app->db->prepare($sql);
    $sth ->bindValue(':rating',$inputOrder['rating']);
    $sth ->bindValue(':rating_obs',$inputOrder['rating_note']);
    $sth ->bindValue(':plusOne',1);
    $sth ->bindValue(':companyId',$inputOrder['company_id']);
    $sth ->bindValue(':orderId',$inputOrder['order_id']);
    $sth ->execute();
    if ($sth->rowCount() > 0) {
        if ($inputOrder['rating']>=4) {
        $this->mrGoodNews("Cliente avaliou loja com ".$inputOrder['rating']." ⭐\nComentarios: ".$inputOrder['rating_note']);
        }else{
        $this->houston("Cliente avaliou loja com ".$inputOrder['rating']." ⭐\nComentarios: ".$inputOrder['rating_note']);
        }
        return $response->withJson(true);
    }else{
        return $response->withJson(false);
    }
}

function SchedulingRating($request, $response){
    $inputScheduling = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputScheduling['user_id'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sql = "UPDATE scheduling, company 
    SET
    scheduling.rating = :rating,
    scheduling.rating_note = :rating_obs, 
    company.rating = company.rating+:rating, 
    company.num_rating = company.num_rating+:plusOne
    WHERE company.company_id = :companyId AND scheduling.scheduling_id = :schedulingId and scheduling.rating < 1;
    ";
    $sth = $this->app->db->prepare($sql);
    $sth->bindValue(':rating',$inputScheduling['rating']);
    $sth->bindValue(':rating_obs',$inputScheduling['rating_note']);
    $sth->bindValue(':plusOne',1);
    $sth->bindValue(':companyId',$inputScheduling['company_id']);
    $sth->bindValue(':schedulingId',$inputScheduling['scheduling_id']);
    $sth->execute();
        if ($sth->rowCount() > 0) {
            if ($inputScheduling['rating']>=4) {
            $this->mrGoodNews("Cliente avaliou loja com ".$inputScheduling['rating']." ⭐\nComentarios: ".$inputScheduling['rating_note']);
        }else{
            $this->houston("Cliente avaliou loja com ".$inputScheduling['rating']." ⭐\nComentarios: ".$inputScheduling['rating_note']);
        }
        return $response->withJson(true);
    }else{
        return $response->withJson(false);
    }
}

function getCompanyDescription($request, $response, $args){
    //TODO TOKEN HERE

     $sth = $this->app->db->prepare("
        SELECT * from company where company.company_id = :companyId;

        SELECT hours.company_id, hours.weekday_id, DATE_FORMAT(hours.start_time, '%H:%i') as start_time, hours.weekday_end_id, DATE_FORMAT(hours.end_time, '%H:%i') as end_time from opening_hours as hours where hours.company_id = :companyId order by hours.weekday_id;

          SELECT * from payment where EXISTS (select payco.company_id from payment_company as payco where payco.company_id = :companyId and payment.payment_id = payco.payment_id);
        ");     
    $sth->bindValue(':companyId',$args['companyId']);
    $sth->execute();
    $company = $sth->fetchObject();
    $sth->nextRowset();
    $schedule = $sth->fetchAll();
    $sth->nextRowset();
    $payment = $sth->fetchAll();

     $arrayJson = array(
        "company" => $company,
        "schedule" => $schedule,
        "payment" => $payment
    );
    return $response->withJson($arrayJson);
}

//=============================================PRODUCTS===============================================================

function getListProduct($request,$response,$args){
     $token = $request->getAttribute("token");
    if (!($args['companyId'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }
    
    $sth = $this->app->db->prepare("
        SELECT * FROM product WHERE product.company_id = :companyId ORDER BY product.position");
    $sth->bindParam("companyId", $args['companyId']);
    $sth->execute();
    $product = $sth->fetchAll();
    return $response->withJson($product);
}

function getProduct($request,$response,$args){
    $token = $request->getAttribute("token");

    $sth = $this->app->db->prepare("
        SELECT * FROM product WHERE product_id = :productId;

        SELECT collection.*, pc.product_id, 
        if(collection.min_quantity<=(select count(*) from collection_extra as ce where ce.collection_id = collection.collection_id),0,1) as has_warning
        FROM collection
        join product_collection as pc where pc.product_id = :productId AND pc.collection_id = collection.collection_id
        order by pc.position;

        SELECT ce.collection_id, extra.* FROM collection_extra AS ce 
        join extra on extra.extra_id = ce.extra_id
        WHERE extra.extra_id = ce.extra_id and exists (select pc.collection_id from product_collection AS pc WHERE pc.product_id = :productId AND pc.collection_id = ce.collection_id);

        SELECT size.* from size
        join product on product.product_id = :productId
        where size.product_id = :productId and product.is_multi_stock = 1 order by size.name");

    $sth->bindParam("productId", $args['productId']);
    $sth->execute();
    $product = $sth->fetchObject();
    $sth->nextRowset();
    $collection = $sth->fetchAll();
    $sth->nextRowset();
    $extras = $sth->fetchAll(\PDO::FETCH_GROUP);
    $sth->nextRowset();
    $multiStock = $sth->fetchAll();

    if (!($product->company_id == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    foreach ($collection as &$c) {
        foreach ($extras as $key => $e) {
            if ($c['collection_id'] == $key) {
                $c['extra'] = $e;
            }
        }
    }

    $arrayJson = array(
        "product" => $product,
        "collection" => $collection,
        "multi_stock" => $multiStock
    );

    return $response->withJson($arrayJson);
}

function getListProductAndVerifyCart($request,$response,$args){
    $token = $request->getAttribute("token");
    if (!($args["userId"] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("
        SELECT * FROM product WHERE product.company_id = :companyId ORDER BY product.position;

        SELECT IF(cart.user_id = :userId,1,0)as has_cart,
        sum(cart.quantity) as quantity,
        (sum((product.price + (SELECT COALESCE(sum(size.price),0) FROM size where size.size_id = cart.size_id) + (SELECT COALESCE(SUM(cart_extra.quantity * extra.price),0) FROM cart_extra join extra on cart_extra.extra_id = extra.extra_id where cart_extra.cart_id = cart.cart_id))* cart.quantity)) total_price
        FROM cart
        join product on cart.product_id = product.product_id
        where cart.user_id = :userId and cart.source = 'nuppin'");

    $sth->bindParam(":companyId", $args['companyId']);
    $sth->bindParam(":userId", $args['userId']);
    $sth->execute();

    $product  = $sth->fetchAll();
    $sth->nextRowset();
    $cart = $sth->fetchObject();


    $arrayJson = array(
        "product" => $product,
        "cart" => $cart
    );

    try{
        $this->viewCounter($args['companyId']);
    }catch(\Throwable $e){
        return $response->withJson($arrayJson);
    }

    return $response->withJson($arrayJson);
}

function getListProductCompanyManual($request,$response,$args){
    $token = $request->getAttribute("token");
    if (!($args["userId"] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("
        SELECT * FROM product WHERE product.company_id = :companyId ORDER BY product.position;
    ");

    $sth->bindParam(":companyId", $args['companyId']);
    $sth->execute();

    $product  = $sth->fetchAll();

    $arrayJson = array(
        "product" => $product
    );

    return $response->withJson($arrayJson);
}

function addProduct($request, $response){
    $inputProduct = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputProduct["company_id"] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $keys = array_keys($inputProduct); 
    $productId;

    if($inputProduct["external_code"] != ""){
        $sth2 = $this->app->db->prepare("SELECT product.product_id FROM product WHERE product.external_code = :externalId and product.company_id = :companyId");
        $sth2->bindParam("externalId", $inputProduct['external_code']);
        $sth2->bindParam("companyId", $inputProduct['company_id']);
        $sth2->execute();
        $check = $sth2->fetchObject();

        if ($check) {
            $error = array();
            $error['error_code'] = "001";
            $error['error_message'] = "Já existe um produto seu com esse codigo de referencia";
            $arrayJson = array("error" => $error, "product" => false);
            return $response->withJson($arrayJson);
        }
    }


    $sql = "INSERT INTO product (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")";
    $sth = $this->app->db->prepare($sql);
    foreach ($inputProduct as $key => $value) {
        if($key == "product_id"){
            $productId = $this->uniqidReal(9);
            $sth ->bindValue(":".$key,$productId);
        }else{
            $sth ->bindValue(':'.$key,$value);
        }
    }
    $sth->execute();

    $this->mrGoodNews("Produto cadastrado 👕");

    $inputProduct['product_id'] = $productId;
    $arrayJson = array("product" => $inputProduct);
    return $response->withJson($arrayJson);
}

function updateProduct($request,$response,$args){
    $inputProduct = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputProduct["company_id"] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sets = [];

    if($inputProduct["external_code"] != ""){
        $sth2 = $this->app->db->prepare("SELECT product.product_id FROM product WHERE product.external_code = :externalId and product.company_id = :companyId and product.product_id != :productId");
        $sth2->bindParam("externalId", $inputProduct['external_code']);
        $sth2->bindParam("companyId", $inputProduct['company_id']);
        $sth2->bindParam("productId", $inputProduct['product_id']);
        $sth2->execute();
        $check = $sth2->fetchObject();

        if ($check) {
            $error = array();
            $error['error_code'] = "001";
            $error['error_message'] = "Já existe um produto seu com esse codigo de referencia";
            $arrayJson = array("error" => $error, "product" => false);
            return $response->withJson($arrayJson);
        }
    }

    foreach ($inputProduct as $key => $VALUES) {
        $sets[] = $key." = :".$key;
    }   
    $sql = "UPDATE product SET ".implode(',', $sets)." WHERE product.product_id = :id";
    $sth = $this->app->db->prepare($sql);
    $sth->bindValue(':id',$args['id']);
    foreach ($inputProduct as $key => $value) {
        $sth->bindValue(':'.$key,$value);
    }
    $sth->execute();
    $arrayJson = array("product" => $inputProduct);
    return $response->withJson($arrayJson);
}

function deleteProduct($request,$response,$args){
    $inputProduct = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputProduct["company_id"] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $affectedRows;

    $this->app->db->beginTransaction();
    try{

        $sth1 = $this->app->db->prepare("DELETE FROM cart where cart.product_id = :id;");
        $sth1->bindParam(":id", $inputProduct['product_id']);
        $sth1->execute();

        $sth2 = $this->app->db->prepare("DELETE FROM size WHERE size.product_id = :id ");
        $sth2->bindParam(":id", $inputProduct['product_id']);
        $sth2->execute();

        $sth3 = $this->app->db->prepare("DELETE FROM product_collection as pc WHERE pc.product_id = :id ");
        $sth3->bindParam(":id", $inputProduct['product_id']);
        $sth3->execute();

        $sth = $this->app->db->prepare("DELETE FROM product WHERE product.product_id = :id and product.company_id = :companyId");
        $sth->bindParam(":id", $inputProduct['product_id']);
        $sth->bindParam(":companyId", $inputProduct['company_id']);
        $sth->execute();
        $affectedRows += $sth->rowCount();

        if ($affectedRows > 0) {
            if ($inputProduct["photo"]) {
            $this->deleteS3("product",$inputProduct["product_id"]);
            }

            $sql3 = "SELECT product.product_id FROM product WHERE product.company_id = :companyId";
            $sth3 = $this->app->db->prepare($sql3);
            $sth3->bindValue(':companyId', $inputProduct["company_id"]);
            $sth3->execute();
            $product = $sth3->fetchAll();
            if(sizeof($product) < 1){
                $sql = "UPDATE company set company.visibility = :companyVisibility WHERE company.company_id = :companyId;";
                $sth = $this->app->db->prepare($sql);
                $sth->bindValue(':companyId',$inputProduct["company_id"]);
                $sth->bindValue(':companyVisibility', 0);
                $sth->execute();
            }
        }

        $this->app->db->commit();

        return $response->withJson($affectedRows);

    }catch(\Throwable $e){
        $this->app->db->rollBack();
        return $response->withJson(false);
    }
}

function getCoupon($request, $response, $args){
    $token = $request->getAttribute("token");

    if (!($args["companyId"] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sql = "SELECT coupon.*, count(uc.order_id) as quantity_used FROM coupon 
    left join coupon_users as uc on uc.coupon_id = coupon.coupon_id 
    WHERE coupon.company_id = :companyId
    and TIMESTAMPDIFF(MINUTE, now(), coupon.due_date) >= 1 group by coupon.coupon_id order by coupon.created_date";

    $data = date('Y-m-d H:i');

    $sth = $this->app->db->prepare($sql);
    $sth ->bindValue(':companyId',$args["companyId"]);
    $sth->execute();
    $coupon = $sth->fetchAll();

    foreach ($coupon as $i => $item) {
        if (strtotime($data) < strtotime($coupon[$i]['due_date'])) {
            $date1 = new \DateTime($data);
            $date2 = new \DateTime($coupon[$i]['due_date']);
            $interval = $date1->diff($date2);
            $coupon[$i]['expires_day'] = $interval->d;
            $coupon[$i]['expires_hour'] = $interval->h;
            $coupon[$i]['expires_minute'] = $interval->i;
        }else{
            $coupon[$i]['expires_day'] = 0;
            $coupon[$i]['expires_hour'] = 0;
            $coupon[$i]['expires_minute'] = 0;
        }
    }

    return $response->withJson($coupon);
}

function getCouponUserFromCart($request, $response, $args){
    $token = $request->getAttribute("token");
    if (!($args['userId'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sql =  "SELECT
    coupon.coupon_id,
    coupon.company_id,
    coupon.value,
    coupon.due_date,
    coupon.discount_type,
    coupon.min_purchase,
    coupon.quantity,
    (select count(uc2.order_id) from coupon_users as uc2 where uc2.coupon_id = uc.coupon_id) as quantity_used
    FROM coupon_users as uc
    join coupon on coupon.coupon_id = uc.coupon_id and coupon.company_id = :companyId
    join company on company.company_id = coupon.company_id
    WHERE uc.user_id = :userId and uc.order_id is null and coupon.due_date > :data group by uc.coupon_id order by coupon.created_date";

    $sth = $this->app->db->prepare($sql);
    $sth ->bindValue(':userId',$args["userId"]);
    $sth ->bindValue(':companyId',$args["companyId"]);
    $sth->bindValue(":data", date("Y-m-d H:i:s"));  
    $sth->execute();
    $coupon = $sth->fetchAll();

    $data = date('Y-m-d H:i');
    foreach ($coupon as $i => $item) {
        if (strtotime($data) < strtotime($coupon[$i]['due_date'])) {
            $date1 = new \DateTime($data);
            $date2 = new \DateTime($coupon[$i]['due_date']);
            $interval = $date1->diff($date2);
            $coupon[$i]['expires_day'] = $interval->d;
            $coupon[$i]['expires_hour'] = $interval->h;
            $coupon[$i]['expires_minute'] = $interval->i;
        }else{
            $coupon[$i]['expires_day'] = 0;
            $coupon[$i]['expires_hour'] = 0;
            $coupon[$i]['expires_minute'] = 0;
        }
    }

    $arrayJson = array('coupon' => $coupon);
    return $response->withJson($arrayJson);
}

function getCouponUsers($request, $response, $args){
    $token = $request->getAttribute("token");

    if (!($args['userId'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sql = "
    SELECT
    coupon.coupon_id,
    coupon.company_id,
    coupon.value,
    coupon.due_date,
    coupon.discount_type,
    coupon.min_purchase,
    coupon.quantity,
    (select count(ucs.order_id) from coupon_users as ucs where ucs.coupon_id = uc.coupon_id) as quantity_used
    FROM coupon_users as uc
    join coupon on coupon.coupon_id = uc.coupon_id
    join company on company.company_id = coupon.company_id
    WHERE uc.user_id = :userId and uc.order_id is null and coupon.due_date > :data and

    (case when company.model_type = 'fixed' then (ST_Distance_Sphere(POINT(company.longitude, company.latitude), POINT(:lo, :la))/1000) else ((case when IF(EXISTS(SELECT mobile.company_id FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL),1,0) = 1 then (SELECT ST_Distance_Sphere(POINT(mobile.longitude, mobile.latitude), POINT(:lo, :la))/1000 FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL) else (ST_Distance_Sphere(POINT(company.longitude, company.latitude), POINT(:lo, :la))/1000)end))end) < company.max_radius and company.status = :active and company.visibility = 1 and (company.is_delivery = :setado or company.is_local = :setado) group by uc.coupon_id order by coupon.created_date
    ";

    $sth = $this->app->db->prepare($sql);
    $sth ->bindValue(':userId',$args["userId"]);
    $sth->bindValue(":data", date("Y-m-d H:i:s"));  
    $sth->bindValue(":active", "active"); 
    $sth->bindValue(":setado", "1");    
    $sth->bindValue(":la", $args['latitude']);
    $sth->bindValue(":lo", $args['longitude']);   
    $sth->execute();
    $coupon = $sth->fetchAll();


    $data = date('Y-m-d H:i');
    foreach ($coupon as $i => $item) {
        if (strtotime($data) < strtotime($coupon[$i]['due_date'])) {
            $date1 = new \DateTime($data);
            $date2 = new \DateTime($coupon[$i]['due_date']);
            $interval = $date1->diff($date2);
            $coupon[$i]['expires_day'] = $interval->d;
            $coupon[$i]['expires_hour'] = $interval->h;
            $coupon[$i]['expires_minute'] = $interval->i;
        }else{
            $coupon[$i]['expires_day'] = 0;
            $coupon[$i]['expires_hour'] = 0;
            $coupon[$i]['expires_minute'] = 0;
        }

        $sql1 = "SELECT * from company where company.company_id = :companyId";
        $sth1 = $this->app->db->prepare($sql1);
        $sth1->bindValue(':companyId',$coupon[$i]['company_id']);
        $sth1->execute();
        $coupon[$i]['company'] = $sth1->fetchObject();
    }

    $arrayJson = array('coupon' => $coupon);
    return $response->withJson($arrayJson);
}

function addCoupon($request, $response,$args){
    $inputCoupon = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputCoupon['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sql;

    $this->app->db->beginTransaction();
    try{

    if ($inputCoupon["target"] == 1) {
        //send ticket for everyone in lat lon
        $sql = "SELECT address.user_id FROM address WHERE (case when :companyModelType = 'fixed' then (ST_Distance_Sphere(POINT(:companyLongitude, :companyLatitude), POINT(address.longitude, address.latitude))/1000) else ((case when IF(EXISTS(SELECT mobile.company_id FROM mobile where mobile.company_id = :companyId and mobile.end_date IS NULL),1,0) = 1 then (SELECT ST_Distance_Sphere(POINT(:companyLongitude, :companyLatitude),POINT(mobile.longitude, mobile.latitude))/1000 FROM mobile where mobile.company_id = :companyId and mobile.end_date IS NULL) else (ST_Distance_Sphere(POINT(:companyLongitude, :companyLatitude),POINT(address.longitude, address.latitude))/1000)end))end) < :companyRadius AND address.is_selected = :selected";

        $sth = $this->app->db->prepare($sql);
        $sth->bindValue(':companyLatitude',$args["latitude"]);
        $sth->bindValue(':companyLongitude',$args["longitude"]);
        $sth->bindValue(':companyRadius',$args["radius"]);
        $sth->bindValue(':companyModelType',$args["modelType"]);
        $sth->bindValue(':companyId',$inputCoupon["company_id"]);
        $sth->bindValue(':selected',1);

    }else if ($inputCoupon["target"] == 2) {

        if($args["category"] == 3){
            $sql = "SELECT address.user_id FROM address 
            WHERE (case when :companyModelType = 'fixed' then (ST_Distance_Sphere(POINT(:companyLongitude, :companyLatitude), POINT(address.longitude, address.latitude))/1000) else ((case when IF(EXISTS(SELECT mobile.company_id FROM mobile where mobile.company_id = :companyId and mobile.end_date IS NULL),1,0) = 1 then (SELECT ST_Distance_Sphere(POINT(:companyLongitude, :companyLatitude),POINT(mobile.longitude, mobile.latitude))/1000 FROM mobile where mobile.company_id = :companyId and mobile.end_date IS NULL) else (ST_Distance_Sphere(POINT(:companyLongitude, :companyLatitude),POINT(address.longitude, address.latitude))/1000)end))end) < :companyRadius AND address.is_selected = :selected AND NOT EXISTS (SELECT scheduling.user_id FROM scheduling WHERE scheduling.company_id = :companyId AND scheduling.user_id = address.user_id); ";
        }else{
            $sql = "SELECT address.user_id FROM address 
            WHERE (case when :companyModelType = 'fixed' then (ST_Distance_Sphere(POINT(:companyLongitude, :companyLatitude), POINT(address.longitude, address.latitude))/1000) else ((case when IF(EXISTS(SELECT mobile.company_id FROM mobile where mobile.company_id = :companyId and mobile.end_date IS NULL),1,0) = 1 then (SELECT ST_Distance_Sphere(POINT(:companyLongitude, :companyLatitude),POINT(mobile.longitude, mobile.latitude))/1000 FROM mobile where mobile.company_id = :companyId and mobile.end_date IS NULL) else (ST_Distance_Sphere(POINT(:companyLongitude, :companyLatitude),POINT(address.longitude, address.latitude))/1000)end))end) < :companyRadius AND address.is_selected = :selected AND NOT EXISTS (SELECT orders.user_id FROM orders WHERE orders.company_id = :companyId AND orders.user_id = address.user_id); ";
        }
        $sth = $this->app->db->prepare($sql);
        $sth->bindValue(':companyLatitude',$args["latitude"]);
        $sth->bindValue(':companyLongitude',$args["longitude"]);
        $sth->bindValue(':companyRadius',$args["radius"]);
        $sth->bindValue(':companyModelType',$args["modelType"]);
        $sth->bindValue(':companyId',$inputCoupon["company_id"]);
        $sth->bindValue(':selected',1);
            
    }else if($inputCoupon["target"] == 3){

        if($args["category"] == 3){
            $sql = "SELECT address.user_id FROM address 
            WHERE (case when :companyModelType = 'fixed' then (ST_Distance_Sphere(POINT(:companyLongitude, :companyLatitude), POINT(address.longitude, address.latitude))/1000) else ((case when IF(EXISTS(SELECT mobile.company_id FROM mobile where mobile.company_id = :companyId and mobile.end_date IS NULL),1,0) = 1 then (SELECT ST_Distance_Sphere(POINT(:companyLongitude, :companyLatitude),POINT(mobile.longitude, mobile.latitude))/1000 FROM mobile where mobile.company_id = :companyId and mobile.end_date IS NULL) else (ST_Distance_Sphere(POINT(:companyLongitude, :companyLatitude),POINT(address.longitude, address.latitude))/1000)end))end) < :companyRadius AND address.is_selected = :selected AND EXISTS (SELECT scheduling.user_id FROM scheduling WHERE scheduling.company_id = :companyId AND scheduling.user_id = address.user_id); ";
        }else{
            $sql = "SELECT address.user_id FROM address 
            WHERE (case when :companyModelType = 'fixed' then (ST_Distance_Sphere(POINT(:companyLongitude, :companyLatitude), POINT(address.longitude, address.latitude))/1000) else ((case when IF(EXISTS(SELECT mobile.company_id FROM mobile where mobile.company_id = :companyId and mobile.end_date IS NULL),1,0) = 1 then (SELECT ST_Distance_Sphere(POINT(:companyLongitude, :companyLatitude),POINT(mobile.longitude, mobile.latitude))/1000 FROM mobile where mobile.company_id = :companyId and mobile.end_date IS NULL) else (ST_Distance_Sphere(POINT(:companyLongitude, :companyLatitude),POINT(address.longitude, address.latitude))/1000)end))end) < :companyRadius AND address.is_selected = :selected AND EXISTS (SELECT orders.user_id FROM orders WHERE orders.company_id = :companyId AND orders.user_id = address.user_id); ";
        }
        $sth = $this->app->db->prepare($sql);
        $sth->bindValue(':companyLatitude',$args["latitude"]);
        $sth->bindValue(':companyLongitude',$args["longitude"]);
        $sth->bindValue(':companyRadius',$args["radius"]);
        $sth->bindValue(':companyModelType',$args["modelType"]);
        $sth->bindValue(':companyId',$inputCoupon["company_id"]);
        $sth->bindValue(':selected',1);
    }

    $sth->execute();
    $users = $sth->fetchAll();
    $keys = array_keys($inputCoupon);
    $couponId;
    $affectedRows;
    $userId;

    if (sizeof($users) < 1) {
         $error = array();
        switch ($inputCoupon["target"]) {
            case 1:
                //$error['error_code'] = "001";
                //$error['error_message'] = "Nenhum usuario por perto para gerar cupom";
                $sql2 = "INSERT INTO coupon (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")";
                $sth2 = $this->app->db->prepare($sql2);
                foreach ($inputCoupon as $key => $value) {
                    if($key == "coupon_id"){
                        $couponId = $this->uniqidReal(9);
                        $sth2 ->bindValue(":".$key,$couponId);
                    }else if($key == "due_date"){
                        $sth2 ->bindValue(':'.$key,date('Y-m-d H:i:s',strtotime("+$value days", strtotime(date('Y-m-d H:i:s')))));
                    }else{
                        $sth2 ->bindValue(':'.$key,$value);
                    }
                }
                $sth2->execute();
                $this->app->db->commit();
                return $response->withJson($sth2->rowCount());
                break;
            case 2:
                $error['error_code'] = "002";
                $error['error_message'] = "Você não tem novos clientes por perto para gerar cupons";
                break;
            case 3:
                $error['error_code'] = "003";
                $error['error_message'] = "Você não tem clientes por perto para gerar cupons";
                break;
        }
                $arrayJson = array("error" => $error);
                return $response->withJson($arrayJson);
    }

        $sql2 = "INSERT INTO coupon (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")";
        $sth2 = $this->app->db->prepare($sql2);
        foreach ($inputCoupon as $key => $value) {
            if($key == "coupon_id"){
                $couponId = $this->uniqidReal(9);
                $sth2 ->bindValue(":".$key,$couponId);
            }else if($key == "due_date"){
                $sth2 ->bindValue(':'.$key,date('Y-m-d H:i:s',strtotime("+$value days", strtotime(date('Y-m-d H:i:s')))));
            }else{
                $sth2 ->bindValue(':'.$key,$value);
            }
        }
        $sth2->execute();


        $sql3 = "INSERT INTO coupon_users (coupon_id, user_id) VALUES(:couponId,:userId)";
        $sth3 = $this->app->db->prepare($sql3);
        foreach ($users as $key1 => $value1) {
            foreach ($value1 as $key2 => $value1) {
                $userId = $value1;
            }
            $sth3 ->bindValue(':userId',$userId);
            $sth3 ->bindValue(':couponId',$couponId);
            $sth3->execute();
            $affectedRows += $sth3->rowCount(); 
        }    

        $this->send_notification_coupon_available($couponId, $inputCoupon['company_id'], $this->app->db);

        $this->mrGoodNews("Cupom criado 🎫");

        $this->app->db->commit();

        return $response->withJson($affectedRows);

    }catch(\Throwable $e){
        $this->app->db->rollBack();
        return $response->withJson(false);
    }
}


// =========================== SERVICOS ===========================================================

function updateStatusScheduling($request, $response, $args){
    $inputScheduling = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputScheduling['company_id'] == $token["comp"] || $inputScheduling['company_id'] == $token["emp_comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sql3 = "SELECT scheduling.status FROM scheduling WHERE scheduling.scheduling_id = :schedulingId";
    $sth3 = $this->app->db->prepare($sql3);
    $sth3->bindValue(':schedulingId', $inputScheduling['scheduling_id']);
    $sth3->execute();
    $scheduling = $sth3->fetchObject();
    if($scheduling->status == "canceled_user" || 
        $scheduling->status == "canceled_company" || 
        $scheduling->status == "no-show"){
         $error = array();
        $error['error_code'] = "001";
        $error['error_message'] = "Não é possivel confirmar, pois o cliente já cancelou o agendamento!";
        $arrayJson = array("error" => $error);
        return $response->withJson($arrayJson);
    }else if($scheduling->status == $args['idStatus']){
        $error = array();
        $error['error_code'] = "001";
        $error['error_message'] = "O status desse pedido já foi atualizado!";
        $arrayJson = array("error" => $error);
        return $response->withJson($arrayJson);
    }


    $this->app->db->beginTransaction();
    try{

        $sql;
        switch($args['idStatus']){
            case "accepted":
            $sql = "UPDATE scheduling SET scheduling.status = :status, scheduling.accepted_date = now() WHERE scheduling.scheduling_id = :schedulingId and scheduling.user_id = :userId and scheduling.company_id = :companyId";
            break;
            case "concluded_not_rated":
                if ($inputScheduling['source'] == 'nuppin_company') {
                    $args['idStatus'] = "concluded";
                    $this->mrGoodNews("Agendamento manual concluido 😍"); 
                }else{
                    $this->mrGoodNews("Agendamento concluido 😍"); 
                }
            case "canceled_company":
            case "no-show":
            case "canceled_refused":
            case "concluded":
               $sql = "UPDATE scheduling SET scheduling.status = :status, scheduling.completed_date = now() WHERE scheduling.scheduling_id = :schedulingId and scheduling.user_id = :userId and  scheduling.company_id = :companyId";
            break;
        }
        $sth = $this->app->db->prepare($sql);
        $sth ->bindValue(':status',$args['idStatus']);
        $sth ->bindValue(':schedulingId', $inputScheduling['scheduling_id']);
        $sth ->bindValue(':userId', $inputScheduling['user_id']);
        $sth ->bindValue(':companyId', $inputScheduling['company_id']);
        $sth->execute();

        if ($sth->rowCount() > 0) {  
            if(!($inputScheduling['source'] == "nuppin_company")){
                switch ($args['idStatus']){
                    case "canceled_refused":
                        $this->send_notification_cancel_order($inputScheduling['user_id'],"Seu agendamento não foi concluido em ".$inputScheduling['company_name'], "Agendamento Cancelado 😟", $this->app->db, "user");
                        $this->houston("Agendamento não aceito 😟");
                    break;
                    case "accepted":
                        $this->send_notification($inputScheduling['user_id'],"Seu agendamento foi aceito em ".$inputScheduling['company_name'], "Agendamento confirmado 😃", $this->app->db, "user");
                    break;
                    case "concluded":
                        $this->UserRating($inputScheduling['user_id'], $args['rating'] ); 
                    break;
                    case "canceled_company":
                        $this->houston("Agendamento cancelado pelo estabelecimento 😟");
                    break;
                    case "no-show":
                        $this->houston("Cliente não compareceu ao agendamento 😟");
                    break;
                }
            }
            $this->app->db->commit();
            return $response->withJson(true);      
        }else{
            return $response->withJson(false);
        }
    }catch(\Throwable $e){
       $this->app->db->rollBack();
        return $response->withJson(false)->withStatus(500);
    }
}

function updateStatusSchedulingFromUser($request, $response, $args){
    $inputScheduling = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputScheduling['user_id'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sql3 = "SELECT * FROM scheduling WHERE scheduling.scheduling_id = :schedulingId";
    $sth3 = $this->app->db->prepare($sql3);
    $sth3->bindValue(':schedulingId', $inputScheduling['scheduling_id']);
    $sth3->execute();
    $scheduling = $sth3->fetchObject();
    if($scheduling->status == "canceled_user" || 
        $scheduling->status == "canceled_company" || 
        $scheduling->status == "no-show"){
         return $response->withJson(false);
    }


    $sql;
    switch($args['idStatus']){
        case "canceled_user":
           $sql = "UPDATE scheduling SET scheduling.status = :status, scheduling.completed_date = now() WHERE scheduling.scheduling_id = :schedulingId and scheduling.user_id = :userId";
        break;
    }
    $sth = $this->app->db->prepare($sql);
    $sth ->bindValue(':status',$args['idStatus']);
    $sth ->bindValue(':schedulingId', $inputScheduling['scheduling_id']);
    $sth ->bindValue(':userId', $inputScheduling['user_id']);
    $sth->execute();

    if ($sth->rowCount() > 0) {
        $inputScheduling['status'] = $args['idStatus'];
        if($scheduling->source == "nuppin"){
            if ($args['idStatus'] == "canceled_user"){
                $this->send_notification_cancel_scheduling($inputScheduling['company_id'],$inputScheduling['employee_id'],"Agendamento: ".strtoupper($inputScheduling['scheduling_id'])." foi cancelado", "Agendamento Cancelado", $this->app->db, "company_scheduling");
                $this->houston("Agendamento cancelado pelo usuario 😟");
            }
        }
        return $response->withJson(true);      
    }else{
        return $response->withJson(false);
    }
}

function getListService($request,$response,$args){
    $token = $request->getAttribute("token");

    $sth = $this->app->db->prepare("SELECT * FROM service WHERE service.company_id = :companyId order by service.position");
    $sth->bindParam("companyId", $args['companyId']);
    $sth->execute();
    $service = $sth->fetchAll();

    $arrayJson = array('service' => $service);
    return $response->withJson($arrayJson);
}    


function getListServiceUser($request,$response,$args){
    $token = $request->getAttribute("token");

    $sth = $this->app->db->prepare("SELECT * FROM service WHERE service.company_id = :companyId order by service.position");
    $sth->bindParam("companyId", $args['companyId']);
    $sth->execute();
    $service = $sth->fetchAll();

    $arrayJson = array('service' => $service);

    try{
        $this->viewCounter($args['companyId']);
    }catch(\Throwable $e){
        return $response->withJson($arrayJson);
    }

    return $response->withJson($arrayJson);
}    

function getScheduling($request,$response,$args){
    $token = $request->getAttribute("token");

    if (!($args['userId'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $timezone = $this->get_nearest_timezone($args['latitude'],$args['longitude'],$args['countryCode']);
    date_default_timezone_set($timezone);

    $sth = $this->app->db->prepare("
        SELECT 
        scheduling.scheduling_id,
        scheduling.company_id,
        scheduling.user_id,
        scheduling.company_name, 
        scheduling.address, 
        scheduling.status, 
        scheduling.service_name, 
        scheduling.employee_name,
        scheduling.employee_id, 
        scheduling.subtotal_amount, 
        scheduling.service_duration, 
        scheduling.total_amount,
        scheduling.payment_method,
        scheduling.discount_amount,
        scheduling.coupon_id,
        if(TIMESTAMPDIFF(HOUR, scheduling.completed_date, now()) >= 1, 0,1) as is_chat_available,
        date(CONVERT_TZ(scheduling.created_date, 'UTC', :timezone)) as created_date, 
        DATE_FORMAT(scheduling.start_time, '%H:%i') as start_time, 
        DATE_FORMAT(scheduling.end_time, '%H:%i') as end_time,     
        date(scheduling.start_time)as start_date, 
        scheduling.note,
        scheduling.rating,
        scheduling.rating_note,
        scheduling.latitude,
        scheduling.longitude,
        company.photo
        FROM scheduling 
        join company on company.company_id = scheduling.company_id
        WHERE scheduling.scheduling_id = :schedulingId;

        SELECT chat.chat_id from chat where chat.seen_date is null and chat.order_id = :schedulingId and chat.chat_from != :userId;");

    $sth->bindParam(":schedulingId", $args['schedulingId']);
    $sth->bindParam(":userId", $args['userId']);
    $sth->bindValue(":timezone", $timezone);
    $sth->execute();
    $scheduling = $sth->fetchObject();
    $sth->nextRowset();
    $chat = $sth->fetchAll();


    $arrayJson = array(
        "scheduling" => $scheduling,
        "chat" => $chat
    );

    return $response->withJson($arrayJson);
}

function getEmployeeScheduling($request,$response,$args){

    $sth = $this->app->db->prepare("
        SELECT employee.employee_id, employee.user_name, employee.start_time, employee.end_time, service.duration, hours.end_time as company_end_time, hours.start_time as company_start_time FROM service_employee AS servemp 
        join employee on employee.employee_id = servemp.employee_id and employee.status = :status
        join service on service.service_id = servemp.service_id
        join opening_hours as hours on hours.company_id = service.company_id
        WHERE servemp.service_id = :serviceId and employee.employee_id = servemp.employee_id and hours.weekday_id = DAYOFWEEK(:data);

        SELECT scheduling.employee_id, date(scheduling.start_time) as start_date, time(scheduling.start_time) as start_time, time(scheduling.end_time) as end_time, scheduling.service_duration FROM scheduling 
        join service_employee as servemp on servemp.service_id = :serviceId
        WHERE scheduling.employee_id = servemp.employee_id and date(scheduling.start_time) = :data 
        and scheduling.status NOT LIKE :cancel
        order by scheduling.start_time;      
        ");
    $sth->bindParam(":serviceId", $args['serviceId']);
    $sth->bindValue(":data", $args['data']);
    $sth->bindValue("status", "active");
    $sth->bindValue(":cancel", "%cancel%");
    $sth->execute();
    $employee = $sth->fetchAll();
    $sth->nextRowset();
    $scheduling = $sth->fetchAll();

    date_default_timezone_set($this->get_nearest_timezone($args['latitude'],$args['longitude'],$args['countryCode']));
    $hour = date('H:i');
    $day = strtotime(date('Y-m-d'));
    $dayUser = strtotime($args['data']);

    for($k = 0; $k < sizeof($employee);$k++){
        $ocupadosComKey = array();
        $todos = array();

        $event_time;
        if ($employee[$k]['start_time'] < $employee[$k]['company_start_time']) {
            $event_time = $employee[$k]['company_start_time'];
        }else{
            $event_time = $employee[$k]['start_time'];
        }
        $time_block;
        if ($employee[$k]['end_time'] > $employee[$k]['company_end_time']) {
            $time_block = $employee[$k]['company_end_time']; 
        }else{
            $time_block = $employee[$k]['end_time']; 
        }
        $event_length = $employee[$k]['duration']; 
        $i = strtotime($event_time);


        if($dayUser >= $day){
            while($i <= strtotime($time_block)) {

                if($i >= strtotime($hour) || $dayUser > $day){

                    $ok = 1;

                    if(strtotime("+$event_length minutes", $i) <= strtotime($time_block)){
                        $new_event_time = date('H:i', $i);
                        $termino = strtotime("+$event_length minutes", $i);
                        $new_event_time2 = date('H:i', $termino);
                        $novo = array(
                            "start_date" => $args['data'],
                            "start_time"=>$new_event_time,
                            "end_time"=>$new_event_time2,
                            "employee_id"=>$employee[$k]['employee_id'],
                            "employee_name"=>$employee[$k]['user_name']);
                        $todos[$i] = $novo;
                    }

                    for($j = 0; $j < sizeof($scheduling);$j++){
                        if ((strtotime($scheduling[$j]['end_time']) >= strtotime("+$event_length minutes", $i) && strtotime($scheduling[$j]['start_time']) < strtotime("+$event_length minutes", $i) && $employee[$k]['employee_id'] == $scheduling[$j]['employee_id'])
                            ||
                            (strtotime($scheduling[$j]['end_time']) <= strtotime("+$event_length minutes", $i) && strtotime($scheduling[$j]['end_time']) > $i && $employee[$k]['employee_id'] == $scheduling[$j]['employee_id'])
                        ){
                            $new_event_time = date('H:i', $i);
                        $termino = strtotime("+$event_length minutes", $i);
                        $new_event_time2 = date('H:i', $termino);
                        $ocupados = array("$i"=> array(
                            "start_date"=> $args['data'],
                            "start_time"=>$new_event_time,
                            "end_time"=>$new_event_time2,
                            "employee_id"=>$employee[$k]['employee_id'],
                            "employee_name"=>$employee[$k]['user_name']));
                        $ocupadosComKey[$i] = $ocupados;
                        $i = strtotime($scheduling[$j]['end_time']);
                        $ok = 2;
                        break;

                    }else{
                        continue;
                    }
                }

                if($ok == 1){
                    $i = strtotime("+$event_length minutes", $i);   
                }   
            }else{
                $i = strtotime("+$event_length minutes", $i);
            }
        }
    }
        $result = array_diff_key($todos,$ocupadosComKey);
        $employee[$k]['scheduling'] = array_values($result);
        $arrayJson = array(
            'employee' => $employee
        );
    }

    if (sizeof($employee) < 1) {
        $arrayJson = array(
            'employee' => []
        );
    }

    return $response->withJson($arrayJson);
}

function addScheduling($request, $response){
    $inputScheduling = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputScheduling['user_id'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    //todo, fazer a vereficação igual ao orders aqui, para garantir integridade do agendamento

    $keys = array_keys($inputScheduling); 
    $schedulingId;  

        $sthCoupon = $this->app->db->prepare("SELECT * FROM scheduling WHERE scheduling.start_time = :start and scheduling.end_time = :end and scheduling.employee_id = :employeeId and scheduling.status NOT LIKE :cancel;");
        $sthCoupon->bindValue(':start',$inputScheduling['start_time']);
        $sthCoupon->bindValue(':end',$inputScheduling['end_time']);
        $sthCoupon->bindValue(':employeeId',$inputScheduling['employee_id']);
        $sthCoupon->bindValue(":cancel", "%cancel%");
        $sthCoupon->execute();
        $checkScheduling = $sthCoupon->fetchObject();

        if ($checkScheduling) {
            $error = array();
            $error['error_code'] = "001";
            $error['error_message'] = "Já tem um agendamento cadastrado nesse horário";
            $arrayJson = array("error" => $error,"scheduling" => false);
            return $response->withJson($arrayJson);
        }


    $this->app->db->beginTransaction();
    try{
        $sql = "INSERT INTO scheduling (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")";
        $sth = $this->app->db->prepare($sql);
        foreach ($inputScheduling as $key => $value) {
            if($key == "scheduling_id"){
                $schedulingId = $this->uniqidReal(6);
                $sth ->bindValue(":".$key,$schedulingId);
            }else{
                $sth ->bindValue(':'.$key,$value);
            }
        }
        if ($sth->execute()) {
            if ($inputScheduling["coupon_id"]) {   
                    $sql3 ="UPDATE coupon_users set coupon_users.order_id = :schedulingId where coupon_users.coupon_id = :couponId and coupon_users.user_id = :userId";
                    $sth3 = $this->app->db->prepare($sql3);
                    $sth3->bindValue(":schedulingId",$schedulingId);
                    $sth3->bindValue(":couponId",$inputScheduling["coupon_id"]);
                    $sth3->bindValue(":userId",$inputScheduling["user_Id"]);
                    $sth3->execute();
                }
            if (!($inputScheduling["source"] == "nuppin_company")) {
                $this->send_notification_new_scheduling($inputScheduling["company_id"],$inputScheduling["employee_id"],"Clique e atualize o status", "Você tem um novo agendamento", $this->app->db);
                $this->mrGoodNews("Agendamento feito ⌚");
            }else{
            $this->mrGoodNews("Agendamento manual feito ⌚");
            }
        }
        $this->app->db->commit();
        $inputScheduling['scheduling_id'] = $schedulingId;
        $arrayJson = array("scheduling" => $inputScheduling);
        return $response->withJson($arrayJson);
    }catch(\Throwable $e){
        $this->app->db->rollBack();
        return $response->withJson(false);
    }
}

function addService($request, $response){
    $inputService = $request->getParsedBody();
     $token = $request->getAttribute("token");

    if (!($inputService['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $keys = array_keys($inputService); 
    $servId;

    $sql = "INSERT INTO service (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")";
    $sth = $this->app->db->prepare($sql);
    foreach ($inputService as $key => $value) {
        if($key == "service_id"){
            $servId = $this->uniqidReal(9);
            $sth ->bindValue(":".$key,$servId);
        }else{
            $sth ->bindValue(':'.$key,$value);
        }
    }
    $sth->execute();
    $this->mrGoodNews("Serviço cadastrado ✂");
    $inputService['service_id'] = $servId;
    return $response->withJson($inputService);
}

function updateService($request,$response,$args){
    $inputService = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputService['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sets = [];
    foreach ($inputService as $key => $VALUES) {
        $sets[] = $key." = :".$key;
    }   
    $sql = "UPDATE service SET ".implode(',', $sets)." WHERE service.service_id = :id";
    $sth = $this->app->db->prepare($sql);
    $sth ->bindValue(':id',$inputService['service_id']);
    foreach ($inputService as $key => $value) {
        $sth ->bindValue(':'.$key,$value);
    }
    $sth->execute();
    $inputService['service_id'] = $args['id'];
    return $response->withJson($inputService);
}

function deleteService($request,$response,$args){
    $inputService = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputService['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $this->app->db->beginTransaction();
    try{

        $sth1= $this->app->db->prepare("DELETE FROM service_employee where service_employee.service_id = :id");
        $sth1->bindParam(":id", $inputService['service_id']);
        $sth1->execute();

        $sth2 = $this->app->db->prepare("DELETE FROM service WHERE service.service_id = :id");
        $sth2->bindParam(":id", $inputService['service_id']);
        $sth2->execute();
        $affectedRows += $sth2->rowCount();  

        $sql3 = "SELECT service.service_id FROM service WHERE service.company_id = :companyId";
        $sth3 = $this->app->db->prepare($sql3);
        $sth3->bindValue(':companyId', $inputService["company_id"]);
        $sth3->execute();
        $service = $sth3->fetchAll(); 
       if(sizeof($service) < 1){
            $sql = "UPDATE company set company.visibility = :companyVisibility WHERE company.company_id = :companyId;";
            $sth = $this->app->db->prepare($sql);
            $sth->bindValue(':companyId',$inputService["company_id"]);
            $sth->bindValue(':companyVisibility', 0);
            $sth->execute();
        } 

       $this->app->db->commit();

        return $response->withJson($affectedRows);

    }catch(\Throwable $e){
        $this->app->db->rollBack();
        return $response->withJson(false);
    }
}

// =========================== FUNCIONARIO ===========================================================

function getListEmployee($request,$response,$args){
    $token = $request->getAttribute("token");

    if (!($args['companyId'] == $token["comp"])) {
        return $response->withJson($args['companyId'])->withStatus(403);
    }

    $sth = $this->app->db->prepare("
        SELECT
        employee.user_id,
        employee.employee_id, 
        employee.user_name, 
        employee.role,
        employee.company_id,
        DATE_FORMAT(employee.start_time, '%H:%i') as start_time, 
        DATE_FORMAT(employee.end_time, '%H:%i') as end_time, 
        employee.title,
        case employee.role 
            when 'owner' then 1
            when 'admin' then 2
            when 'employee' then 3
        end as role_ranking 
        FROM employee WHERE employee.company_id = :companyId and employee.status = :status order by role_ranking;
        ");
    $sth->bindParam("companyId", $args['companyId']);
    $sth->bindValue("status", "active");
    $sth->execute();
    $employee = $sth->fetchAll();

    $arrayJson = array(
        'employee' => $employee);

    return $response->withJson($arrayJson);
}

function addEmployee($request, $response){
    $inputEmployee = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputEmployee['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $keys = array_keys($inputEmployee);
    $employeeId;

    $sth2 = $this->app->db->prepare("
        SELECT 
        users.full_name,
        users.user_id 
        FROM users WHERE (users.document_number = :identity) and 
        not EXISTS(SELECT employee.user_id from employee where (employee.user_id = users.user_id and employee.status = :status and employee.company_id = :companyId) or (employee.user_id = users.user_id and employee.status = :status and employee.company_id != :companyId and employee.role = :role))");
    $sth2->bindValue(":identity", $inputEmployee['user_id']);//here it is used like users.document_number(cpf), not user_id
    $sth2->bindValue(":role", $inputEmployee['role']);
    $sth2->bindValue(":companyId", $inputEmployee['company_id']);
    $sth2->bindValue("status", "active");
    $sth2->execute();
    $user = $sth2->fetchObject();

    if($user){
        $inputEmployee["user_name"] = $user->full_name;
        $inputEmployee["user_id"] = $user->user_id;//here we set the id to employee.user_id
        $sql = "INSERT INTO employee (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")";
        $sth = $this->app->db->prepare($sql);
        foreach ($inputEmployee as $key => $value) {
            if($key == "employee_id"){
                $employeeId = $this->uniqidReal(9);
                $sth ->bindValue(":".$key,$employeeId);
            }else{
                $sth ->bindValue(':'.$key,$value);
            }
        }
        $sth->execute();
        $inputEmployee['employee_id'] = $employeeId;
        $arrayJson = array("employee" => $inputEmployee);

        $this->mrGoodNews("Empresa adicionou um usuário na equipe 💼");

        return $response->withJson($arrayJson);
    }
    $error = array();
    $error['error_code'] = "001";
    $error['error_message'] = "Usuario não existe ou já esta cadastrado em uma empresa";
    $arrayJson = array("error" => $error,"employee" => false);
    return $response->withJson($arrayJson);

}

function getListEmployeeService($request,$response,$args){
    $token = $request->getAttribute("token");
    //todo token

    if (!($args['companyId'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("
        SELECT 
        employee.user_id,
        employee.company_id,
        employee.employee_id,
        employee.user_name,
        employee.role,
        DATE_FORMAT(employee.start_time, '%H:%i') as start_time,
        DATE_FORMAT(employee.end_time, '%H:%i') as end_time,
        employee.title, employee.company_id
        FROM employee
        join service_employee as servemp on servemp.service_id = :serviceId and employee.employee_id = servemp.employee_id
        where employee.status = :status and employee.company_id = :companyId");
    $sth->bindParam(":serviceId", $args['serviceId']);
    $sth->bindValue("status", "active");
    $sth->bindParam(":companyId", $args['companyId']);
    $sth->execute();
    $employee = $sth->fetchAll();

    $arrayJson = array('employee' => $employee);
    return $response->withJson($arrayJson);
}

function getListEmployeeNotService($request,$response,$args){
    $token = $request->getAttribute("token");
    
    if (!($args['companyId'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("
        SELECT
        employee.employee_id, 
        employee.user_id, 
        employee.user_name, 
        employee.company_id,
        employee.role,
        DATE_FORMAT(employee.start_time, '%H:%i') as start_time,
        DATE_FORMAT(employee.end_time, '%H:%i') as end_time, 
        employee.title 
        FROM employee WHERE employee.company_id = :companyId and employee.status = :status AND NOT EXISTS (SELECT employee.employee_id FROM service_employee as servemp WHERE servemp.service_id = :id and employee.employee_id = servemp.employee_id)");
    $sth->bindParam(":id", $args['id']);
    $sth->bindParam(":companyId", $args['companyId']);
    $sth->bindValue("status", "active");
    $sth->execute();
    $employee = $sth->fetchAll();
    $arrayJson = array('employee' => $employee);
    return $response->withJson($arrayJson);
}

function addEmployeeService($request, $response){
    $input = $request->getParsedBody();
    $token = $request->getAttribute("token");

    //todo token
    if (!($input['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $keys = array_keys($input); 

    $sql = "INSERT INTO service_employee (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")";
    $sth = $this->app->db->prepare($sql);
    foreach ($input as $key => $value) {
        $sth ->bindValue(':'.$key,$value);
    }
    $sth->execute();
    return $response->withJson($input);
}

function updateEmployee($request,$response){
    $inputEmployee = $request->getParsedBody();
    $token = $request->getAttribute("token");
    
    if (!($inputEmployee['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sets = [];
    foreach ($inputEmployee as $key => $VALUES) {
        $sets[] = $key." = :".$key;
    }   
    $sql = "UPDATE employee SET ".implode(',', $sets)." WHERE employee.employee_id = :id";
    $sth = $this->app->db->prepare($sql);
    $sth ->bindValue(':id',$inputEmployee['employee_id']);
    foreach ($inputEmployee as $key => $value) {
        $sth ->bindValue(':'.$key,$value);
    }
    $sth->execute();
    $affectedRows += $sth->rowCount();
    return $response->withJson($affectedRows);
}

function deleteEmployee($request,$response,$args){
    $inputEmployee = $request->getParsedBody();
    $token = $request->getAttribute("token");
    
    if (!($inputEmployee['company_id'] == $token["comp"] || $inputEmployee['employee_id'] == $token["emp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $this->app->db->beginTransaction();
    try{

        $sth1 = $this->app->db->prepare("DELETE FROM service_employee where service_employee.employee_id = :employeeId");
        $sth1->bindParam(":employeeId", $inputEmployee['employee_id']);
        $sth1->execute();

        $sth2 = $this->app->db->prepare("UPDATE employee SET employee.status = :status, employee.fired_date = now() WHERE employee.employee_id = :employeeId and employee.role = :role");
        $sth2->bindParam(":employeeId", $inputEmployee['employee_id']);
        $sth2->bindParam(":role", $inputEmployee['role']);
        $sth2->bindValue(":status", "inactive");
        $sth2->execute();
        $affectedRows += $sth2->rowCount();
        $this->app->db->commit();

        return $response->withJson($affectedRows);

    }catch(\Throwable $e){
        $this->app->db->rollBack();
        return $response->withJson(false);
    }
}

function deleteEmployeeService($request,$response){
    $inputEmployee = $request->getParsedBody();
    $token = $request->getAttribute("token");
    
    if (!($inputEmployee['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("DELETE FROM service_employee WHERE service_employee.employee_id = :employeeId and service_employee.service_id = :servId");
    $sth->bindParam(":employeeId", $inputEmployee['employee_id']);
    $sth->bindParam(":servId", $inputEmployee['service_id']);
    $sth->execute();
    $affectedRows = $sth->rowCount();
    return $response->withJson($affectedRows);
}

function getCompanyReport($request, $response, $args){
    $token = $request->getAttribute("token");
    
    if (!($args['companyId'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $timezone = $this->get_nearest_timezone($args['latitude'],$args['longitude'],$args['countryCode']);
    date_default_timezone_set($timezone);

    $sth = $this->app->db->prepare("
    SELECT 
    COUNT(case when orders.status LIKE :cancel then 1 end) as canceled_orders, 
    COUNT(case when orders.status LIKE :concluded then 1 end) as concluded_orders, 
    SUM(case when orders.status LIKE :concluded then orders.total_amount end) AS orders_total_value,
    truncate(avg(case when orders.status LIKE :concluded then orders.total_amount end),2) as average_ticket
    FROM company 
    left join orders on orders.company_id = company.company_id and date(CONVERT_TZ(orders.created_date,'UTC',:timezone)) BETWEEN :data1 and :data2
    WHERE company.company_id = :companyId;

    SELECT count(*) AS recurring_customer
    FROM (
    SELECT o.user_id FROM orders as o where o.company_id = :companyId and o.status LIKE :concluded and date(CONVERT_TZ(o.created_date,'UTC',:timezone)) BETWEEN :data1 AND :data2 and exists(select o2.user_id from orders as o2 where o2.order_id != o.order_id and o2.user_id = o.user_id AND o2.company_id = o.company_id)
    GROUP BY o.user_id)AS t;

    SELECT count(*) AS new_customer
    FROM (
    SELECT o.user_id FROM orders as o where o.company_id = :companyId and o.status LIKE :concluded and date(CONVERT_TZ(o.created_date,'UTC',:timezone)) BETWEEN :data1 AND :data2 and not exists(select o2.user_id from orders as o2 where o2.order_id != o.order_id and o2.user_id = o.user_id AND o2.company_id = o.company_id)
    GROUP BY o.user_id)AS t2;

    SELECT 
    SUM(case when finance.amount < 0 then finance.amount end) as expenses,
    SUM(case when finance.amount > 0 then finance.amount end) as revenue
    FROM finance WHERE finance.company_id = :companyId and finance.reference_date BETWEEN :data1 and :data2;

    SELECT 
    sum(view_count.count) as view_count, 
    truncate(avg(view_count.count),0) as tm_view_count
    FROM company
    left join view_count on view_count.company_id = company.company_id and date(view_count.created_date) BETWEEN :data1 and :data2
    WHERE company.company_id = :companyId;
    ");
    
    $sth->bindParam(":companyId", $args['companyId']);  
    $sth->bindParam(":data1", $args['data1']);
    $sth->bindParam(":data2", $args['data2']);
    $sth->bindParam(":timezone", $timezone);
    $sth->bindValue(":cancel", "%cancel%");
    $sth->bindValue(":concluded", "%concluded%");
    $sth->execute();
    $CompanyReport = $sth->fetchObject();
    $sth->nextRowset(); 
    $CompanyReport->recurring_customer = $sth->fetchObject()->recurring_customer;
    $sth->nextRowset(); 
    $CompanyReport->new_customer = $sth->fetchObject()->new_customer;
    $sth->nextRowset(); 
    $financeiro = $sth->fetchObject();
    $CompanyReport->expenses = $financeiro->expenses;
    $CompanyReport->revenue = $financeiro->revenue;
    $sth->nextRowset(); 
    $views = $sth->fetchObject();
    $CompanyReport->view_count = $views->view_count;
    $CompanyReport->tm_view_count = $views->tm_view_count;

    $arrayJson = array(
        'company_data' => $CompanyReport);

    return $response->withJson($arrayJson);
}

function getCompanyReportScheduling($request, $response, $args){
    $token = $request->getAttribute("token");
    
    if (!($args['companyId'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("
        SELECT 
        COUNT(case when scheduling.status LIKE :cancel then 1 end) as canceled_orders, 
        COUNT(case when scheduling.status LIKE :concluded then 1 end) as concluded_orders,
        SUM(case when scheduling.status LIKE :concluded then scheduling.total_amount end) AS orders_total_value,
        truncate(avg(case when scheduling.status LIKE :concluded then scheduling.total_amount end),2) as average_ticket
        FROM company
        left join scheduling on scheduling.company_id = company.company_id and date(scheduling.start_time) BETWEEN :data1 and :data2
        WHERE company.company_id = :companyId;

        SELECT count(*) AS recurring_customer
        FROM (
        SELECT o.user_id FROM scheduling as o where o.company_id = :companyId and o.status LIKE :concluded and date(o.start_time) BETWEEN :data1 AND :data2 and exists(select o2.user_id from scheduling as o2 where o2.scheduling_id != o.scheduling_id and o2.user_id = o.user_id AND o2.company_id = o.company_id)
        GROUP BY o.user_id)AS t;

        SELECT count(*) AS new_customer
        FROM (
        SELECT o.user_id FROM scheduling as o where o.company_id = :companyId and o.status LIKE :concluded and date(o.start_time) BETWEEN :data1 AND :data2 and not exists(select o2.user_id from scheduling as o2 where o2.scheduling_id != o.scheduling_id and o2.user_id = o.user_id AND o2.company_id = o.company_id)
        GROUP BY o.user_id)AS t2;
 
        SELECT 
        SUM(case when finance.amount < 0 then finance.amount end) as expenses, 
        SUM(case when finance.amount > 0 then finance.amount end) as revenue 
        FROM finance WHERE finance.company_id = :companyId and finance.reference_date BETWEEN :data1 and :data2;

        SELECT 
        sum(view_count.count) as view_count, 
        truncate(avg(view_count.count),0) as tm_view_count
        FROM company
        left join view_count on view_count.company_id = company.company_id and date(view_count.created_date) BETWEEN :data1 and :data2
        WHERE company.company_id = :companyId;
        ");

    $sth->bindParam(":companyId", $args['companyId']);  
    $sth->bindParam(":data1", $args['data1']);
    $sth->bindParam(":data2", $args['data2']);
    $sth->bindValue(":cancel", "%cancel%");
    $sth->bindValue(":concluded", "%concluded%");
    $sth->execute();
    $CompanyReport = $sth->fetchObject();
    $sth->nextRowset(); 
    $CompanyReport->recurring_customer = $sth->fetchObject()->recurring_customer;
    $sth->nextRowset(); 
    $CompanyReport->new_customer = $sth->fetchObject()->new_customer;
    $sth->nextRowset(); 
    $financeiro = $sth->fetchObject();
    $CompanyReport->expenses = $financeiro->expenses;
    $CompanyReport->revenue = $financeiro->revenue;
    $sth->nextRowset(); 
    $views = $sth->fetchObject();
    $CompanyReport->view_count = $views->view_count;
    $CompanyReport->tm_view_count = $views->tm_view_count;

    $arrayJson = array(
        'company_data' => $CompanyReport);

    return $response->withJson($arrayJson);
}


function getFinance($request, $response, $args){
    //todo - colocar o convert_tz no cashflow_date para pegar o cadastro com o fuso correto
    $token = $request->getAttribute("token");
    
    if (!($args['companyId'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("
        SELECT * FROM finance WHERE finance.company_id = :companyId and date(finance.reference_date) BETWEEN :data1 and :data2 order by finance.reference_date;
        ");
    $sth->bindParam(":companyId", $args['companyId']);  
    $sth->bindParam(":data1", $args['data1']);
    $sth->bindParam(":data2", $args['data2']);
    $sth->execute();
    $cashFlow = $sth->fetchAll();
    $arrayJson = array('finance' => $cashFlow);

    return $response->withJson($arrayJson);
}


function getChat($request, $response, $args){
    $token = $request->getAttribute("token");
    
    if (!($args['companyId'] == $token["comp"] || $args['companyId'] == $token["emp_comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("
        SELECT chat.*, chatnotseen.quantity_not_seen, users.full_name as user_name FROM chat
        join users on users.user_id = chat.user_id
        JOIN (SELECT chat2.order_id, MAX(chat2.created_date) as created_date, COUNT(case when chat2.seen_date is null and chat2.chat_from = chat2.user_id then 1 end) as quantity_not_seen FROM chat as chat2 GROUP BY chat2.order_id) as chatnotseen
        ON chat.order_id = chatnotseen.order_id AND chat.created_date = chatnotseen.created_date
        where chat.company_id = :companyId GROUP BY chat.order_id order by chat.created_date desc;
        ");
    $sth->bindParam(":companyId", $args['companyId']);
    $sth->execute();
    $chat = $sth->fetchAll();

    $arrayJson = array(
        'chat' => $chat);

    return $response->withJson($arrayJson);
}


function getOrderHistory($request, $response, $args){
    $token = $request->getAttribute("token");
    
    if (!($args['companyId'] == $token["comp"] || $args['companyId'] == $token["emp_comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $timezone = $this->get_nearest_timezone($args['latitude'],$args['longitude'],$args['countryCode']);
    date_default_timezone_set($timezone); 

    $sth = $this->app->db->prepare(  "
        SELECT
        upper(orders.order_id) as order_id, 
        orders.total_amount, 
        date(orders.created_date) as created_date, 
        orders.user_name, 
        orders.payment_method, 
        orders.status 
        FROM orders WHERE orders.company_id = :companyId and date(CONVERT_TZ(orders.created_date,'UTC',:timezone)) BETWEEN :data1 and :data2 order by orders.created_date desc;");
    $sth->bindValue(":companyId", $args['companyId']);  
    $sth->bindValue(":data1", $args['data1']);
    $sth->bindValue(":data2", $args['data2']);
    $sth->bindValue(":timezone", $timezone);
    $sth->execute();
    $historicoOrder = $sth->fetchAll();
    $arrayJson = array(
        'order' => $historicoOrder
    );
    return $response->withJson($arrayJson);
}

function getSchedulingHistory($request, $response, $args){
    $token = $request->getAttribute("token");
    
    if (!($args['companyId'] == $token["comp"] || $args['companyId'] == $token["emp_comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("
        SELECT 
        upper(scheduling.scheduling_id) as scheduling_id, 
        scheduling.total_amount, 
        date(scheduling.start_time) as start_time, 
        scheduling.user_name, 
        scheduling.payment_method, 
        scheduling.status 
        FROM scheduling WHERE scheduling.company_id = :companyId and date(scheduling.start_time) BETWEEN :data1 and :data2 order by scheduling.start_time desc;");
    $sth->bindParam(":companyId", $args['companyId']);  
    $sth->bindParam(":data1", $args['data1']);
    $sth->bindParam(":data2", $args['data2']);
    $sth->execute();
    $historicoScheduling = $sth->fetchAll();
    $arrayJson = array(
        'scheduling' => $historicoScheduling
    );
    return $response->withJson($arrayJson);
}

function getOrderRating($request, $response, $args){
    $token = $request->getAttribute("token");
    
    if (!($args['companyId'] == $token["comp"] || $args['companyId'] == $token["emp_comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $timezone = $this->get_nearest_timezone($args['latitude'],$args['longitude'],$args['countryCode']);
    date_default_timezone_set($timezone); 

    $sth = $this->app->db->prepare("SELECT upper(orders.order_id) as order_id, orders.rating, date(orders.created_date) as created_date, orders.rating_note FROM orders WHERE orders.company_id = :companyId and date(CONVERT_TZ(orders.created_date,'UTC',:timezone)) BETWEEN :data1 and :data2 and orders.rating > 0 order by orders.created_date desc;");
    $sth->bindValue(":companyId", $args['companyId']);  
    $sth->bindValue(":data1", $args['data1']);
    $sth->bindValue(":data2", $args['data2']);
    $sth->bindValue(":timezone", $timezone);
    $sth->execute();
    $avaliacoes = $sth->fetchAll();
    $arrayJson = array(
        'rating' => $avaliacoes);
    return $response->withJson($arrayJson);
}

function getSchedulingRating($request, $response, $args){
    $token = $request->getAttribute("token");
    
    if (!($args['companyId'] == $token["comp"] || $args['companyId'] == $token["emp_comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("SELECT 
        upper(schedulin.scheduling_id) as scheduling_id, 
        scheduling.rating, 
        date(scheduling.start_time) as start_time, 
        scheduling.rating_note 
        FROM scheduling WHERE scheduling.company_id = :companyId and date(scheduling.start_time) BETWEEN :data1 and :data2 and scheduling.rating > 0 order by scheduling.start_time desc;");
    $sth->bindParam(":companyId", $args['companyId']);  
    $sth->bindParam(":data1", $args['data1']);
    $sth->bindParam(":data2", $args['data2']);
    $sth->execute();
    $avaliacoes = $sth->fetchAll();
    $arrayJson = array(
        'rating' => $avaliacoes);
    return $response->withJson($arrayJson);
}

function addCompanyPayment($request, $response){
    $inputPaycom = $request->getParsedBody();
    $token = $request->getAttribute("token");
    
    if (!($inputPaycom['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $keys = array_keys($inputPaycom); 

    $sql = "INSERT INTO payment_company (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")";
    $sth = $this->app->db->prepare($sql);
    foreach ($inputPaycom as $key => $value) {
        $sth ->bindValue(':'.$key,$value);
    }
    $sth->execute();
    return $response->withJson($inputPaycom);
}

function deleteCompanyPayment($request,$response){
    $inputPaycom = $request->getParsedBody();
    $token = $request->getAttribute("token");
    
    if (!($inputPaycom['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth1 = $this->app->db->prepare("
        SELECT * from payment where EXISTS (select payco.company_id from payment_company as payco where payco.company_id = :sto and payment.payment_id = payco.payment_id)
        ");     
    $sth1->bindValue(":sto", $inputPaycom['company_id']);
    $sth1->execute();
    $payment = $sth1->fetchAll();

    if (sizeof($payment)>1) {
        $sth = $this->app->db->prepare("DELETE FROM payment_company as payco WHERE payco.company_id = :id and payco.payment_id = :pag");
        $sth->bindParam(":id", $inputPaycom['company_id']);
        $sth->bindParam(":pag", $inputPaycom['payment_id']);
        $sth->execute();
        $affectedRows = $sth->rowCount();
        return $response->withJson($affectedRows);
    }else{
        $error = array();
        $error['error_code'] = "002";
        $error['error_message'] = "Minimo de uma forma de pagamento cadastrada";
        $arrayJson = array("payment_error" => $error);
        return $response->withJson($arrayJson);
    }
}

function getSubcategory($request, $response, $args){
    $sql = "SELECT sc.subcategory_company_id, sc.name FROM
    subcategory_company as sc WHERE sc.category_company_id = :category
    ";
    $sth = $this->app->db->prepare($sql);
    $sth ->bindValue(':category',$args['category']);
    $sth ->execute();   
    $subcategory = $sth->fetchAll();
    return $response->withJson($subcategory);
}

function getPaymentMethod($request, $response, $args){
    //THIS METHOD IS USED IN THE PAYMENT CONFIGURATION COMPANY

    $token = $request->getAttribute("token");
    
    if (!($args['companyId'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("
        SELECT payment.*, 
        IF(EXISTS (select * from payment_company as payco where payco.company_id = :companyId and payment.payment_id = payco.payment_id),1,0)as is_checked 
        from payment;
        ");     
    $sth ->bindValue(':companyId',$args['companyId']);
    $sth->execute();
    $payment = $sth->fetchAll();
    return $response->withJson($payment);
}

function getCompanyPayment($request, $response, $args){
//THIS METHOD IS USED TO GET THE PAYMENT METHODS OPTIONS IN USER CART AND IN THE CONFIGURATION COMPANY

    //TODO TOKEN
    $sth = $this->app->db->prepare("
        SELECT * from payment where EXISTS (select payco.company_id from payment_company as payco where payco.company_id = :companyId and payment.payment_id = payco.payment_id)
        ");
    $sth ->bindValue(':companyId',$args['companyId']);
    $sth->execute();
    $payment = $sth->fetchAll();
    return $response->withJson($payment);
}

function addCompanySchedule($request, $response){
    $inputOpeningHours = $request->getParsedBody();
    $token = $request->getAttribute("token");
    
    if (!($inputOpeningHours[0]['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    foreach ($inputOpeningHours as $key => $value) {
        $keys = array_keys($value); 

        $sql = "INSERT INTO opening_hours (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")";
        $sth = $this->app->db->prepare($sql);
        foreach ($value as $key1 => $value1) {
            $sth ->bindValue(':'.$key1,$value1);
        }
        $sth->execute();
    }
    return $response->withJson($inputOpeningHours);
}

function updateCompanySchedule($request,$response){
    $inputOpeningHours = $request->getParsedBody();
    $token = $request->getAttribute("token");
    
    if (!($inputOpeningHours['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sets = [];
    foreach ($inputOpeningHours as $key => $VALUES) {
        $sets[] = $key." = :".$key;
    }   
    $sql = "UPDATE opening_hours as hours SET ".implode(',', $sets)." WHERE hours.company_id = :companyId and hours.weekday_id = :diaId";
    $sth = $this->app->db->prepare($sql);
    $sth ->bindValue(':companyId',$inputOpeningHours['company_id']);
    $sth ->bindValue(':diaId',$inputOpeningHours['weekday_id']);
    foreach ($inputOpeningHours as $key => $value) {
        $sth ->bindValue(':'.$key,$value);
    }
    $sth->execute();
    $affectedRows += $sth->rowCount();
    return $response->withJson($affectedRows);
}

function deleteCompanySchedule($request, $response, $args){
    $inputOpeningHours = $request->getParsedBody();
    $token = $request->getAttribute("token");
    
    if (!($inputOpeningHours['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("DELETE FROM opening_hours as hours WHERE hours.company_id = :companyId and hours.weekday_id = :weekday");
    $sth->bindParam(":companyId", $inputOpeningHours['company_id']);
    $sth->bindParam(":weekday", $inputOpeningHours['weekday_id']);
    $sth->execute();
    return $response->withJson($sth->rowCount());
}

function getCompanySchedule($request, $response, $args){
    $token = $request->getAttribute("token");
    
    if (!($args['companyId'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("
        SELECT 
        hours.company_id, 
        hours.weekday_id, 
        DATE_FORMAT(hours.start_time, '%H:%i') as start_time, 
        hours.weekday_end_id, 
        DATE_FORMAT(hours.end_time, '%H:%i') as end_time 
        from opening_hours as hours where hours.company_id = :sto order by hours.weekday_id;
        ");     
    $sth ->bindValue(':sto',$args['companyId']);
    $sth->execute();
    $dias = $sth->fetchAll();
    
    return $response->withJson($dias);
}

function getUndefinedCompanySchedule($request, $response, $args){
    $token = $request->getAttribute("token");
    
    if (!($args['companyId'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("
        SELECT 
        weekday.weekday_id 
        from weekday where not exists(select hours.company_id from opening_hours as hours where hours.company_id = :sto AND hours.weekday_id = weekday.weekday_id)
        ");     
    $sth ->bindValue(':sto',$args['companyId']);
    $sth->execute();
    $dias = $sth->fetchAll();
    
    return $response->withJson($dias);
}

function mobileCompanyOn($request, $response){
    $inputMobile = $request->getParsedBody();
    $token = $request->getAttribute("token");
    
    if (!($inputMobile['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $keys = array_keys($inputMobile);
    $mobileId;

    $sql = "INSERT INTO mobile (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")";
    $sth = $this->app->db->prepare($sql);
    foreach ($inputMobile as $key1 => $value1) {
        if ($key1 == "mobile_id") {
            $mobileId = $this->uniqidReal(9);
            $sth->bindValue(':'.$key1,$mobileId);
        }else{
            $sth->bindValue(':'.$key1,$value1);
        }
    }
    $sth->execute();
    $inputMobile["mobile_id"] = $mobileId;
    return $response->withJson($inputMobile);
}

function mobileCompanyUpdate($request, $response){
    $inputMobile = $request->getParsedBody();
    $token = $request->getAttribute("token");
    
    if (!($inputMobile['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("SELECT * from mobile where mobile.company_id = :companyId and mobile.end_date is null");
    $sth ->bindValue(':companyId',$inputMobile['company_id']);
    $sth->execute();
    $mobile = $sth->fetchObject();

    $this->app->db->beginTransaction();
    try{

    $sth2 = $this->app->db->prepare("SELECT mobile.mobile_id from mobile where mobile.company_id = :companyId and mobile.latitude = 0 and mobile.longitude = 0");
    $sth2->bindValue(':companyId',$inputMobile['company_id']);
    $sth2->execute();
    $mobile2 = $sth2->fetchObject();

    if($mobile2){
        $sql3 = "UPDATE mobile SET mobile.latitude = :latitude, mobile.longitude = :longitude WHERE mobile.mobile_id = :mobileId";
        $sth3 = $this->app->db->prepare($sql3);
        $sth3 ->bindValue(':mobileId',$mobile2->mobile_id);
        $sth3 ->bindValue(':latitude',$inputMobile['latitude']);
        $sth3 ->bindValue(':longitude',$inputMobile['longitude']);
        $sth3->execute();
    }else if($mobile){
        $this->mobileCompanyOff($request, $response);
        $this->mobileCompanyOn($request, $response);
    }
    
    $this->app->db->commit();
    
    }catch(\Throwable $e){
        $this->app->db->rollBack();
    }
}

function mobileCompanyOff($request,$response){
    $inputMobile = $request->getParsedBody();
    $token = $request->getAttribute("token");
    
    if (!($inputMobile['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sql = "UPDATE mobile SET mobile.end_date = now() WHERE mobile.company_id = :companyId and mobile.end_date is null";
    $sth = $this->app->db->prepare($sql);
    $sth ->bindValue(':companyId',$inputMobile['company_id']);
    $sth->execute();
    $affectedRows += $sth->rowCount();
    return $response->withJson($affectedRows);}

function getInvoice($request, $response, $args){
    $token = $request->getAttribute("token");
    
    if (!($args['companyId'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("
        SELECT * from invoice where invoice.company_id = :companyId order by invoice.created_date desc;
        ");     
    $sth ->bindValue(':companyId',$args['companyId']);
    $sth->execute();
    $invoice = $sth->fetchAll();
    
    return $response->withJson($invoice);}

function getInvoiceDetail($request, $response, $args){
    $token = $request->getAttribute("token");

    $sth = $this->app->db->prepare("
        SELECT invoice.*, ip.code_line, ip.external_link, company.category_company_id from invoice 
        left join invoice_payment as ip on ip.invoice_id = invoice.invoice_id
        join company on company.company_id = invoice.company_id
        where invoice.invoice_id = :id;
        ");     
    $sth ->bindValue(':id',$args['invoice_id']);
    $sth->execute();
    $fatura = $sth->fetchObject();

    if (!($fatura->company_id == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    return $response->withJson($fatura);
}

function getInvoiceOrderDetail($request, $response, $args){
    $token = $request->getAttribute("token");
    
    if (!($args['companyId'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("
        SELECT orders.order_id, 
        orders.total_amount,
        date(orders.created_date) as created_date, 
        orders.user_name, 
        orders.payment_method,
        (case when (SELECT i.fee_amount FROM invoice as i WHERE i.company_id = :id and i.created_date = :createdDate) > 0 then(
        truncate(orders.total_amount * pc.fee ,2))else(0)end) as invoice_fee     
        from orders
        join plan_company as pc on pc.company_id = :id
        LEFT JOIN invoice ON invoice.invoice_id = (SELECT b.invoice_id FROM invoice as b WHERE b.company_id = :id and b.created_date < :createdDate order by b.created_date desc limit 1)
        where orders.company_id = :id and DATE_FORMAT(orders.completed_date, '%Y-%m-%d') >= invoice.created_date and DATE_FORMAT(orders.completed_date, '%Y-%m-%d') < :createdDate and (orders.status = 'concluded_not_rated' or  orders.status = 'concluded') and orders.source = :nuppin");

    $sth->bindValue(':id',$args['companyId']);
    $sth->bindValue(':createdDate',$args['date']);
    $sth->bindValue(':nuppin',"nuppin");
    $sth->execute();
    $historicoOrder = $sth->fetchAll();
    $arrayJson = array("order" => $historicoOrder);
    return $response->withJson($arrayJson);
}

function getInvoiceSchedulingDetail($request, $response, $args){
    $token = $request->getAttribute("token");
    
    if (!($args['companyId'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("
        SELECT scheduling.scheduling_id, 
        scheduling.total_amount, 
        date(scheduling.start_time) as start_time, 
        scheduling.user_name, 
        scheduling.payment_method,
        (case when (SELECT i.fee_amount FROM invoice as i WHERE i.company_id = :id and i.created_date = :createdDate) > 0 then(
        truncate(scheduling.total_amount * pc.fee ,2))else(0)end) as invoice_fee     
        from scheduling
        join plan_company as pc on pc.company_id = :id
        LEFT JOIN invoice ON invoice.invoice_id = (SELECT b.invoice_id FROM invoice as b WHERE b.company_id = :id and b.created_date < :createdDate order by b.created_date desc limit 1)
         where scheduling.company_id = :id and DATE_FORMAT(scheduling.completed_date, '%Y-%m-%d') >= invoice.created_date and  DATE_FORMAT(scheduling.completed_date, '%Y-%m-%d') < :createdDate and (scheduling.status = 'concluded_not_rated' or scheduling.status = 'concluded') and scheduling.source = :nuppin");

    $sth->bindValue(':id',$args['companyId']);
    $sth->bindValue(':createdDate',$args['date']);
    $sth->bindValue(':nuppin',"nuppin");
    $sth->execute();
    $historicoScheduling = $sth->fetchAll();
    $arrayJson = array("scheduling" => $historicoScheduling);
    return $response->withJson($arrayJson);
}

function getPlan($request, $response, $args){
    $sth = $this->app->db->prepare("
        SELECT plan.plan_id, plan.name, plan.price, truncate(plan.fee*100,0) as fee, plan.trial_period, plan.trial_price, truncate(plan.trial_fee*100,0) as trial_fee from plan where plan.category_company_id = :categoryId;

        SELECT benefit.description FROM plan
        join plan_benefit AS pb on pb.plan_id = plan.plan_id
        join benefit on benefit.benefit_id = pb.benefit_id
        WHERE plan.category_company_id = :categoryId order by benefit.position;
        ");

    $sth->bindValue(':categoryId',$args['categoryId']);
    $sth->execute();
    $plan = $sth->fetchObject();
    $sth->nextRowset();
    $benefit = $sth->fetchAll();

    $plan->benefit = $benefit;

    $this->mrGoodNews("Tem alguem olhando os planos 🤞");

    return $response->withJson($plan);
}

function addFinance($request, $response){
    $inputFinance = $request->getParsedBody();
    $token = $request->getAttribute("token");
    
    if (!($inputFinance['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $keys = array_keys($inputFinance); 
    $cashId;

    $sql = "INSERT INTO finance (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")";
    $sth = $this->app->db->prepare($sql);
    foreach ($inputFinance as $key => $value) {
        if($key == "finance_id"){
            $cashId = $this->uniqidReal(9);
            $sth ->bindValue(":".$key,$cashId);
        }else{
            $sth ->bindValue(':'.$key,$value);
        }
    }
    if ($sth->execute()) {
        $inputFinance['finance_id'] = $cashId;

        $this->mrGoodNews("Registro de finanças foi criado 🏷");

        return $response->withJson($inputFinance);
    }else{
        return $response->withJson(false);
    }}

function updateFinance($request,$response,$args){
    $inputFinance = $request->getParsedBody();
    $token = $request->getAttribute("token");
    
    if (!($inputFinance['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sets = [];
    foreach ($inputFinance as $key => $VALUES) {
        $sets[] = $key." = :".$key;
    }   
    $sql = "UPDATE finance SET ".implode(',', $sets)." WHERE finance.finance_id = :id";
    $sth = $this->app->db->prepare($sql);
    $sth->bindValue(':id',$inputFinance['finance_id']);
    foreach ($inputFinance as $key => $value) {
        $sth->bindValue(':'.$key,$value);
    }
    $sth->execute();
    $returnValue = $sth->rowCount();
    return $response->withJson($returnValue);
}

function deleteFinance($request, $response, $args){
    $inputFinance = $request->getParsedBody();
    $token = $request->getAttribute("token");
    
    if (!($inputFinance['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("DELETE FROM fiance WHERE finance_id = :financeId and finance.company_id = :companyId");
    $sth->bindParam(":companyId", $inputFinance['company_id']);
    $sth->bindParam(":cashflowId", $inputFinance['finance_id']);
    $sth->execute();

     if ($input["photo"]) {
        $this->deleteS3("finance",$inputFinance["finance_id"]);
    }

    return $response->withJson($sth->rowCount());
}

function addMessageChat($request, $response, $args){
    $inputChat = $request->getParsedBody();
    $token = $request->getAttribute("token");
    
    //todo token

    if (!(($inputChat['chat_from'] == $token["comp"] || $inputChat['chat_from'] == $token["emp_comp"]) || $inputChat['chat_from'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $keys = array_keys($inputChat);

    $sql = "INSERT INTO chat (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")";
    $sth = $this->app->db->prepare($sql);
    $msgId;

    foreach ($inputChat as $key => $value) {
        if($key == "chat_id"){
            $msgId = $this->uniqidReal(16);
            $sth->bindValue(":".$key,$msgId);
        }else{
            $sth->bindValue(':'.$key,$value);
        }
    }
    $sth->execute();

    $this->send_notification_chat($inputChat["chat_to"],$inputChat["chat_from"],$inputChat["order_id"], "💬 Nova mensagem no chat", "Pedido: ".strtoupper($inputChat["order_id"]),$this->app->db,$args['tag']);

    $this->joe("From: ".$inputChat["chat_from"]." • pedido :".$inputChat["order_id"]."\n💬 ".$inputChat["message"]);

    $inputChat["chat_id"] = $msgId;
    return $response->withJson($inputChat);
}

function getMessageFromChat($request, $response, $args){
    $token = $request->getAttribute("token");

    //todo token

    $sql = "SELECT * FROM chat WHERE chat.order_id = :orderId order by chat.created_date
    ";
    $sth = $this->app->db->prepare($sql);
    $sth ->bindValue(':orderId',$args['orderId']);
    $sth ->execute();   
    $chat = $sth->fetchAll();

    $isToUpdate = false;//marcar como lida
    $msgOrdId;
    for($i = sizeof($chat)-1; $i >= 0; $i--) {
        if ($chat[$i]["seen_date"] == null && $chat[$i]["chat_from"] != $args['id']) {
            $isToUpdate = true;
            $msgOrdId = $chat[$i]["order_id"];
            break;
        }
    }

    if ($isToUpdate) {
        $seen = date('Y-m-d H:i:s');
        $sql1 = "UPDATE chat SET chat.seen_date = :seen WHERE chat.order_id = :msgOrdId and chat.seen_date is null";
        $sth1 = $this->app->db->prepare($sql1);
        $sth1 ->bindValue(':msgOrdId',$msgOrdId);
        $sth1 ->bindValue(':seen',$seen);
        $sth1 ->execute();
    }

    
     $arrayJson = array(
        "chat" => $chat
        );
    return $response->withJson($arrayJson);
}

function sendEmail($request, $response){
    $inputTempEmail = $request->getParsedBody();

        $sql1 = "SELECT * from temp_email where temp_email.temp_email_id = :email";
        $sth1 = $this->app->db->prepare($sql1);
        $sth1->bindValue(':email',$inputTempEmail['temp_email_id']);
        $sth1->execute();
        $email = $sth1->fetchObject();

        $code;
        $attempts = 0;
        if ($email) {
            if ($email->attempts < 3) {
                $code = $email->code_sent;
                $attempts = $email->attempts;
            }else{
                $code = $this->uniqidReal(6);
            }
        }else{
            $code = $this->uniqidReal(6);
        }

    try{
        $result = $client = $this->sesEmail($inputTempEmail['temp_email_id'], strtoupper($code), $this->templateEmailCode($code));
        $sql = "
        DELETE FROM temp_email where temp_email.temp_email_id = :email;
        INSERT INTO temp_email(temp_email_id, code_sent, attempts) VALUES(:email, :code, :attempts)";
        $sth = $this->app->db->prepare($sql);
        $sth ->bindValue(':email',$inputTempEmail['temp_email_id']);
        $sth ->bindValue(':code',$code);
        $sth ->bindValue(':attempts',$attempts);
        $sth ->execute();
        return $response->withJson(true);
    } catch (\Throwable $e) {
        return $response->withJson($e->getMessage());
    }
}

function sesEmail($destination, $subject, $template){
        $client = SesClient::factory(array('region'=>'us-east-2','version'=> 
       'latest','credentials' => array('key' => 'AKIAYXAUBWB7WBLFHAPG','secret' => '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',)));
        $msg = array();
        $msg['Source'] = "Nuppin <naoresponda@nuppin.com>";
        $msg['Destination']['ToAddresses'][] = $destination;
        $msg['ReplyToAddresses'][] = "naoresponda@nuppin.com";
        $msg['Message']['Subject']['Data'] = $subject;  
        $msg['Message']['Subject']['Charset'] = "utf-8";
        $msg['Message']['Body']['Html']['Data'] = $template;
        $msg['Message']['Body']['Html']['Charset'] = "utf-8";
        return $client->sendEmail($msg);
}

function verifyCodeFromEmail($request, $response){
    $inputTempEmail = $request->getParsedBody();
    try{
        //todo new here
        $sql = "
        SELECT * FROM users WHERE users.email = :email;
        SELECT * FROM temp_email WHERE temp_email.temp_email_id = :email and temp_email.code_sent = :code";
        $sth = $this->app->db->prepare($sql);
        $sth->bindValue(':code',$inputTempEmail['code_sent']);
        $sth->bindValue(':email',$inputTempEmail['temp_email_id']);
        $sth->execute();
        $user = $sth->fetchObject();
        $sth->nextRowset();
        $verified = $sth->fetchObject();
        if($verified){
            if ($verified->attempts < 3) {
            if ($user) {
                $user->refresh_token = $this->addRefreshToken($user->user_id);
                return $response->withJson($user);       
            }
            return $response->withJson(1);
        }else{
            return $response->withJson(3);
        }
        } else{
            $sql1 ="UPDATE temp_email set temp_email.attempts = temp_email.attempts + 1 WHERE temp_email.temp_email_id = :email";
            $sth1 = $this->app->db->prepare($sql1);
            $sth1->bindValue(':email',$inputTempEmail['temp_email_id']);
            $sth1->execute();
            return $response->withJson(2);
        }
    }catch(\Throwable $e){
        return $response->withJson(false)->withStatus(500);
    }
}

function verifyCodeToChangeEmail($request, $response){
    $inputTempEmail = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputTempEmail['user_id'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sql = "
    SELECT * FROM temp_email WHERE temp_email.temp_email_id = :email and temp_email.code_sent = :code";
    $sth = $this->app->db->prepare($sql);
    $sth->bindValue(':code',$inputTempEmail['code_sent']);
    $sth->bindValue(':email',$inputTempEmail['temp_email_id']);
    $sth->execute();
    $verified = $sth->fetchObject();

    if($verified){
        if ($verified->attempts < 3) {
            $sth2 = $this->app->db->prepare("SELECT users.user_id FROM users WHERE users.email = :email");
            $sth2->bindValue(':email',$inputTempEmail['temp_email_id']);
            $sth2->execute();
            $userEmail = $sth2->fetchObject();

            if (!$userEmail) {
                $sql1 = "UPDATE users set users.email = :email WHERE users.user_id = :userId";
                $sth1 = $this->app->db->prepare($sql1);
                $sth1->bindValue(':userId',$inputTempEmail['user_id']);
                $sth1->bindValue(':email',$inputTempEmail['temp_email_id']);
                $sth1->execute();
                return $response->withJson(1);
            }else{
                return $response->withJson(4);
            }
        }else{
            return $response->withJson(3);
        }
    } else{
        $sql1 ="UPDATE temp_email set temp_email.attempts = temp_email.attempts + 1 WHERE temp_email.temp_email_id = :email";
        $sth1 = $this->app->db->prepare($sql1);
        $sth1->bindValue(':email',$inputTempEmail['temp_email_id']);
        $sth1->execute();
        return $response->withJson(2);
    }
}

function sendCodeFromSMS($request, $response){
    $inputTempSms = $request->getParsedBody();

    $sql1 = "SELECT * from temp_sms where temp_sms.temp_sms_id = :phone_number";
    $sth1 = $this->app->db->prepare($sql1);
    $sth1->bindValue(':phone_number',$inputTempSms['temp_sms_id']);
    $sth1->execute();
    $phone_number = $sth1->fetchObject();

    $code;
    $attempts = 0;
    if ($phone_number) {
        if ($phone_number->attempts < 3) {
            $code = $phone_number->code_sent;
            $attempts = $phone_number->attempts;
        }else{
            $code = $this->uniqidReal(6);
        }
    }else{
        $code = $this->uniqidReal(6);
    }

    $client = SnsClient::factory(array(
    'region' => 'us-east-1',
    'version' => 'latest',
    'credentials' => array(
        'key'    => 'AKIAYXAUBWB7RWIZFZPT',
        'secret' => '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')
    ));

    $message = 'Seu codigo de acesso : '.strtoupper($code);

    $payload = [
    'PhoneNumber' => $inputTempSms['temp_sms_id'],
        'Message'          => $message,
        'MessageStructure' => 'string',
        'MessageAttribute' => [
            'AWS.SNS.SMS.SenderID' => [
                'DataType'    => 'String',
                'StringValue' => 'Nuppin',
            ],
            'AWS.SNS.SMS.SMSType'  => [
                'DataType'    => 'String',
                'StringValue' => 'Transactional',
            ]
        ]
    ];
       try{
        $result = $client->publish($payload);
        $sql = "
        DELETE FROM temp_sms where temp_sms.temp_sms_id = :phone_number;
        INSERT INTO temp_sms(temp_sms_id, code_sent, attempts) VALUES(:phone_number, :code, :attempts)";
        $sth = $this->app->db->prepare($sql);
        $sth ->bindValue(':phone_number',$inputTempSms['temp_sms_id']);
        $sth ->bindValue(':code',$code);
        $sth ->bindValue(':attempts',$attempts);
        $sth ->execute();
        return $response->withJson(true);
    } catch (\Throwable $e) {
        return $response->withJson($e->getMessage());
    }
}


function verifyCodeFromPhoneNumber($request, $response){
    $inputTempSms = $request->getParsedBody();
    try{
        //todo new here
        $sql = "
        SELECT * FROM users WHERE users.phone_number = :phone_number;
        SELECT * FROM temp_sms WHERE temp_sms.temp_sms_id = :phone_number and temp_sms.code_sent = :code";
        $sth = $this->app->db->prepare($sql);
        $sth ->bindValue(':code',$inputTempSms['code_sent']);
        $sth ->bindValue(':phone_number',$inputTempSms['temp_sms_id']);
        $sth->execute();
        $user = $sth->fetchObject();
        $sth->nextRowset();
        $verified = $sth->fetchObject();
        if($verified){
            if ($verified->attempts < 3) {
                if ($user) {
                    $user->refresh_token = $this->addRefreshToken($user->user_id);
                    return $response->withJson($user);      
                }
                return $response->withJson(1);
            }else{
                return $response->withJson(3);
            }
        } else{
            $sql1 = "UPDATE temp_sms set temp_sms.attempts =  temp_sms.attempts + 1 WHERE temp_sms.temp_sms_id = :phone_number";
            $sth1 = $this->app->db->prepare($sql1);
            $sth1->bindValue(':phone_number',$inputTempSms['temp_sms_id']);
            $sth1->execute();
            return $response->withJson(2);
        }
     }catch(\Throwable $e){
        return $response->withJson(false)->withStatus(500);
    }
}

function verifyCodeToChangePhoneNumber($request, $response){
    $inputTempSms = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputTempSms['user_id'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sql = "
    SELECT * FROM temp_sms WHERE temp_sms.temp_sms_id = :phone_number and temp_sms.code_sent = :code";
    $sth = $this->app->db->prepare($sql);
    $sth->bindValue(':code',$inputTempSms['code_sent']);
    $sth->bindValue(':phone_number',$inputTempSms['temp_sms_id']);
    $sth->execute();
    $verified = $sth->fetchObject();

    if($verified){
        if ($verified->attempts < 3) {
             $sth2 = $this->app->db->prepare("SELECT users.user_id FROM users WHERE users.phone_number = :phone_number");
            $sth2->bindValue(':phone_number',$inputTempSms['temp_sms_id']);
            $sth2->execute();
            $userPhoneNumber = $sth2->fetchObject();

            if (!$userPhoneNumber) {
                $sql1 = "UPDATE users set users.phone_number = :phone_number WHERE users.user_id = :userId";
                $sth1 = $this->app->db->prepare($sql1);
                $sth1->bindValue(':userId',$inputTempSms['user_id']);
                $sth1->bindValue(':phone_number',$inputTempSms['temp_sms_id']);
                $sth1->execute();
                return $response->withJson(1);
            }else{
                return $response->withJson(4);
            }
        }else{
            return $response->withJson(3);
        }
    } else{
        $sql1 = "UPDATE temp_sms set temp_sms.attempts =  temp_sms.attempts + 1 WHERE temp_sms.temp_sms_id = :phone_number";
        $sth1 = $this->app->db->prepare($sql1);
        $sth1->bindValue(':phone_number',$inputTempSms['temp_sms_id']);
        $sth1->execute();
        return $response->withJson(2);
    }
}

function updateCanceledBoleto($request, $response, $args){
    $inputInvoice = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputInvoice['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }
        //todo new here
    $sql = "SELECT company.name, users.email, users.full_name, company.document_number, company.document_type
            FROM company 
            join employee on employee.company_id = company.company_id
            join users on users.user_id = employee.user_id
            where employee.status = 'active' and employee.role = 'owner' and employee.company_id = :companyId";
    
    $sth = $this->app->db->prepare($sql);
    $sth->bindValue(":companyId",$inputInvoice['company_id']);
    $sth->execute();
    $data = $sth->fetchObject();

    if($data){
        $this->app->db->beginTransaction();
        try{
            $this->generateInvoiceBoleto($documentType, $inputInvoice['price'], $inputInvoice['fee'], $data->email, $data->full_name, $data->document_number, $data->name, $inputInvoice['invoice_id'], $data->companyId);
            $this->app->db->commit();
            $this->mrGoodNews("Boleto foi gerado novamente 🍀");
            return $response->withJson(true);
        }catch(\Throwable $e){
            return $response->withJson(false);
            $this->app->db->rollBack();
        }
    }
}


  function addFeedback($request, $response){
        $inputFeedback = $request->getParsedBody();
        $keys = array_keys($inputFeedback); 

        $sql = "INSERT INTO feedback (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")";
        $sth = $this->app->db->prepare($sql);
        foreach ($inputFeedback as $key => $value) {
            $sth ->bindValue(':'.$key,$value);
        }

        $this->voxPopuli("Novo feedback 📣\n'".$inputFeedback["message"]."'");

        return $response->withJson($sth->execute());
    }

    function addSuggestion($request, $response){
        $inputSuggestion = $request->getParsedBody();
        $keys = array_keys($inputSuggestion); 

        $sql = "INSERT INTO suggestion (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")";
        $sth = $this->app->db->prepare($sql);
        foreach ($inputSuggestion as $key => $value) {
            $sth ->bindValue(':'.$key,$value);
        }

        $this->leadBoy("Sugestão recebida 🗨\nNome: ".$inputSuggestion['company_name']."\nDetalhes: ".$inputSuggestion['detail']."\nInstagram: ".$inputSuggestion['instagram']."\nFacebook: ".$inputSuggestion['facebook']."\nWhatsapp: ".$inputSuggestion['whatsapp']);

        return $response->withJson($sth->execute());
    }

function getPosts($request, $response, $args){
    $sth = $this->app->db->prepare("
        SELECT 
        material.material_id, 
        material.title, 
        material.photo, 
        material.category_material_id, 
        material.subcategory_material_id,
        mc.name as category_name,
        ms.name as subcategory_name
        FROM material
        join category_material as mc on mc.category_material_id = material.category_material_id
        join subcategory_material as ms on ms.subcategory_material_id = material.subcategory_material_id
        where mc.category_material_id = material.category_material_id and ms.subcategory_material_id = material.subcategory_material_id and material.status = 1 order by material.created_date desc LIMIT 10 OFFSET :inicio");
    $sth->bindValue(':inicio',$args['offset'],\PDO::PARAM_INT);
    $sth->execute();
    $material = $sth->fetchAll();
    $arrayJson = array("material" => $material);
    return $response->withJson($arrayJson);}


function getMaterialBody($request, $response, $args){
    $sth = $this->app->db->prepare(
        "SELECT * from material 
        WHERE material.material_id = :materialId and material.status = 1; 
        UPDATE material SET material.view_counter = material.view_counter + 1 where material.material_id = :materialId"
    );
    $sth ->bindValue(':materialId',$args['materialId']);
    $sth->execute();
    $material = $sth->fetchObject();

    $this->mrGoodNews("Estão lendo o artigo '$material->title' 📘");

    return $response->withJson($material);
}
    //FUNÇÕES AUXILIARES OU AUTOMATICAS --------------------------------------------- \/

//FUNÇÃO PARA O UPLOAD DE IMAGENS NO AWS S3
function deleteS3($folder, $id){

        $s3 = new S3Client([
            'region'  => 'us-east-2',
            'version' => 'latest',
            'credentials' => [
                'key'    => "AKIAYXAUBWB75OW26CLO",
                'secret' => "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",
            ]
        ]);

        $s3->deleteMatchingObjects('nuppin-img', $folder.'/'.$id);
}

//FUNÇÃO PARA O UPLOAD DE IMAGENS NO AWS S3
function uploadS3($request, $response, $args){
    if(isset($_FILES['image'])){

        $file = $_FILES["image"]['tmp_name'];

        // We are only allowing images
        $allowedMimes = array(
        'jpg|jpeg|jpe' => 'image/jpeg',
        'png'          => 'image/png',
        );

        if(!in_array(mime_content_type($file),$allowedMimes)){
            return "FALSE";
        }

        $uploadedFiles = $request->getUploadedFiles();
        $uploadedFile = $uploadedFiles['image'];
 
        $s3 = new S3Client([
            'region'  => 'us-east-2',
            'version' => 'latest',
            'credentials' => [
                'key'    => "AKIAYXAUBWB75OW26CLO",
                'secret' => "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",
            ]
        ]);

        $s3->deleteMatchingObjects('nuppin-img', $args['folder'].'/'.$args['id']);

        $ext = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $key = $this->uniqidReal(16).".".$ext;

        $pathAWS = $args['folder']."/".$args['id']."/".$key;

        // Send a PutObject 'request and get the result object.
        $result = $s3->putObject([
            'Bucket' => 'nuppin-img',
            'Key'    => $pathAWS,
            'SourceFile' => $file
        ]);


        if($result['ObjectURL'] != null){

            $sql;
            switch($args['folder']){
                case "company":
                    $this->instagrammer("Empresa adicionou foto de perfil🖼\nLink: ".$result['ObjectURL']);
                    $sql = "UPDATE company SET company.photo = :key where company.company_id = :id";
                break;
                case "company_banner":
                    $this->instagrammer("Empresa adicionou foto de banner 🖼\nLink: ".$result['ObjectURL']);
                    $sql = "UPDATE company SET company.banner_photo = :key where company.company_id = :id";
                break;
                case "product":
                    $this->instagrammer("Foto adicionada ao produto 🖼\nLink: ".$result['ObjectURL']);
                    $sql = "UPDATE product SET product.photo = :key where product.product_id = :id";
                break;
                case "finance":
                    $this->mrGoodNews("Foto adicionada ao registro financeiro 🖼");
                    $sql = "UPDATE finance SET finance.photo = :key where finance.finance_id = :id";
                break;
                case "users":
                    $this->mrGoodNews("Usuário adicionou foto de perfil 🖼");
                    $sql = "UPDATE users SET users.photo = :key where users.user_id = :id";
                break;
            }

              $sth = $this->app->db->prepare($sql);
              $sth->bindValue(":key", $result['ObjectURL']);
              $sth->bindParam(":id", $args['id']);
              $sth->execute();
              return "OK";
        }else{
            return "FALSE";
        }

    }}

//FUNCÃO QUE ATUALIZA O STATUS DO BOLETO (CHAMADO PELO PAGHIPER)
function updateStatusBoleto($request,$response){
    $this->app->db->beginTransaction();
    try{
        $raw = file_get_contents('php://input');
        $data = array();
        $cleanRaw = preg_replace('/\s+/', '', $raw);
        parse_str($cleanRaw, $data);
        $data["token"] = "HKJY0L01W7VNPYZ9H99U85M8GHRYIBLZW4XWPYXM5S9V";
        $status = json_decode($this->boleto_notification($data));

        if($status->status_request->transaction_id != null){
            $boleto = array();
            $boleto['transaction_id'] = $status->status_request->transaction_id;
            $boleto['status'] = $status->status_request->status;

            if ($boleto['status'] == 'paid') {
            $sql3 = "SELECT company.company_id, company.status from invoice
            join invoice_payment as ip on ip.invoice_id = invoice.invoice_id
            join company on company.company_id = invoice.company_id
            where ip.invoice_payment_id = :transactionId AND company.status != :active";

            $sth3 = $this->app->db->prepare($sql3);
            $sth3->bindValue(":active",'active');
            $sth3->bindValue(':transactionId',$boleto['transaction_id']);
            $sth3->execute();
            $company = $sth3->fetchObject();

            if($company){
                $sql2 = "
                UPDATE company
                INNER JOIN invoice ON (invoice.company_id = company.company_id)
                INNER JOIN invoice_payment as ip on ip.invoice_id = invoice.invoice_id
                SET company.status = :active,
                invoice.status = :status,
                invoice.completed_date = :hoje,
                ip.status = :status,
                ip.completed_date = :hoje
                where company.company_id = :companyId and ip.invoice_payment_id = :transactionId and invoice.invoice_id = ip.invoice_id";
                $sth2 = $this->app->db->prepare($sql2);
                $sth2->bindValue(":active",'active');
                $sth2->bindValue(":hoje",date("Y-m-d"));
                $sth2->bindValue(":companyId",$company->company_id);
                $sth2->bindValue(':transactionId',$boleto['transaction_id']);
                $sth2->bindValue(':status',$boleto['status']);
                $sth2->execute();
                $this->mrGoodNews("Boleto pago, status da empresa: ".$company->status." 🤑");
            }else{
                $sql2="
                UPDATE invoice
                INNER JOIN invoice_payment as ip on ip.invoice_id = invoice.invoice_id
                SET
                invoice.status = :status,
                invoice.completed_date = :hoje,
                ip.status = :status,
                ip.completed_date = :hoje
                where ip.invoice_payment_id = :transactionId and invoice.invoice_id = ip.invoice_id";

                $sth2 = $this->app->db->prepare($sql2);
                $sth2->bindValue(":hoje",date("Y-m-d"));
                $sth2->bindValue(':transactionId',$boleto['transaction_id']);
                $sth2->bindValue(':status',$boleto['status']);
                $sth2->execute();
                $this->mrGoodNews("Boleto de empresa ativa, pago 🤑");
            }

            $sth4 = $this->app->db->prepare("SELECT company.company_id, invoice.invoice_id from invoice
            join invoice_payment as ip on ip.invoice_id = invoice.invoice_id
            join company on company.company_id = invoice.company_id
            where ip.invoice_payment_id = :transactionId");
            $sth4->bindValue(':transactionId',$boleto['transaction_id']);
            $sth4->execute();
            $company = $sth4->fetchObject();

            $sth5 = $this->app->db->prepare("DELETE from invoice_payment as ip where ip.invoice_id = :invoiceId and ip.invoice_payment_id != :transactionId");
            $sth5->bindValue(':invoiceId',$company->invoice_id);
            $sth5->bindValue(':transactionId',$boleto['transaction_id']);
            $sth5->execute();

            $this->send_notification_invoice($company->company_id, "Que bom que deu tudo certo", "Sua fatura está paga! 😍", $this->app->db, "company");
            }else{
                $sql="
                UPDATE invoice
                INNER JOIN invoice_payment as ip on ip.invoice_id = invoice.invoice_id
                SET
                invoice.status = :status,
                ip.status = :status
                where ip.invoice_payment_id = :transactionId and invoice.invoice_id = ip.invoice_id";
                $sth = $this->app->db->prepare($sql);
                $sth->bindValue(':transactionId',$boleto['transaction_id']);
                $sth->bindValue(':status',$boleto['status']);
                $sth->execute();

                if ($boleto['status'] == 'canceled') {
                    $this->houston("Um boleto foi cancelado 💸");
                }

            }
        $this->app->db->commit();
        }
    }catch(\Throwable $e){
        $this->app->db->rollBack();
        return $response->withStatus(500);
    }
}


//FUNÇÃO ENVIA O CODIGO DE LINHA RECEBIDO DO PAGHIPER, EM FORMATO DE JSON E COM O TOKEN
function boleto_notification($array){
    $headers = array
            (
                'Content-Type: application/json'
            );
    #Send response To FireBase Server    
    $ch = curl_init();
    curl_setopt( $ch,CURLOPT_URL, 'https://api.paghiper.com/transaction/notification');
    curl_setopt( $ch,CURLOPT_POST, true );
    curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode($array));
    $result = curl_exec($ch );
    curl_close( $ch );
    return $result;
}


//FUNÇÃO QUE PEGA O FUSO HORARIO DO USUARIO
function get_nearest_timezone($cur_lat, $cur_long, $country_code = '') {
    $timezone_ids = ($country_code) ? \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, $country_code)
                                    : \DateTimeZone::listIdentifiers();

    if($timezone_ids && is_array($timezone_ids) && isset($timezone_ids[0])) {

        $time_zone = '';
        $tz_distance = 0;

        //only one identifier?
        if (count($timezone_ids) == 1) {
            $time_zone = $timezone_ids[0];
        } else {

            foreach($timezone_ids as $timezone_id) {
                $timezone = new \DateTimeZone($timezone_id);
                $location = $timezone->getLocation();
                $tz_lat   = $location['latitude'];
                $tz_long  = $location['longitude'];

                $theta    = $cur_long - $tz_long;
                $distance = (sin(deg2rad($cur_lat)) * sin(deg2rad($tz_lat))) 
                + (cos(deg2rad($cur_lat)) * cos(deg2rad($tz_lat)) * cos(deg2rad($theta)));
                $distance = acos($distance);
                $distance = abs(rad2deg($distance));
                // echo '<br />'.$timezone_id.' '.$distance; 

                if (!$time_zone || $tz_distance > $distance) {
                    $time_zone   = $timezone_id;
                    $tz_distance = $distance;
                } 

            }
        }
        if ($time_zone == "America/Sao_Paulo") {
            return "America/Fortaleza";
        }else{
            return  $time_zone;
        }
    }
    return 'unknown';
}


//FUNÇÃO QUE GERA SENQUENCIA DE NUMERO ALEATORIOS
function uniqidReal($lenght) {
    // uniqid gives 13 chars, but you could adjust it to your needs.
    if (function_exists("random_bytes")) {
        $bytes = random_bytes(ceil($lenght / 2));
    } elseif (function_exists("openssl_random_pseudo_bytes")) {
        $bytes = openssl_random_pseudo_bytes(ceil($lenght / 2));
    } else {
        throw new \Exception("no cryptographically secure random function available");
    }
    return substr(bin2hex($bytes), 0, $lenght);
}

//SEND NOTIFICATION FROM FCM
function send_notification($id, $bodyMsg, $TittleMsg, $db, $tag){
    define( 'API_ACCESS_KEY', 'AAAAoyT3prI:APA91bHOXAJI-geJieyMCXeEGkJjD2tDpTAtXiV5Kgxvpe5ViMZjUwe3ts7bJ1ZZD-gCofrzUZAKjUasoefVp2yQRQpPY5psyt2vdaBG_EJjTZPl0-KeII9DZHTvuGNdGPtOnvW6W7-f');

    $sql = $this->companyOrUser($tag);
    $sth = $db->prepare($sql);
    $sth ->bindValue(":id",$id);
    $sth->execute();
    $token = $sth->fetchAll();

    $ids = array();

    foreach ($token as $o) {
        array_push($ids, $o['token']);        
    }

    $msg;
    switch ($tag){
        case "user":
        $data = array(
            'body'  => $bodyMsg,
            'title' => $TittleMsg,
            'tag' => 'order'
        );
        break;
        case "all_company":
        $data = array(
            'body'  => $bodyMsg,
            'title' => $TittleMsg,
            'sound' => 'order',
            'tag' => 'order'
        );
        break;
    }

    $fields = array
    (
        'registration_ids'=> $ids,
        'data'  => $data
    );


    $headers = array
    (
        'Authorization: key=' . API_ACCESS_KEY,
        'Content-Type: application/json'
    );
        #Send response To FireBase Server    
    $ch = curl_init();
    curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
    curl_setopt( $ch,CURLOPT_POST, true );
    curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
    $result = curl_exec($ch );
    curl_close( $ch );
}

//SEND NOTIFICATION FROM FCM
function send_notification_cancel_order($id, $bodyMsg, $TittleMsg, $db, $tag){
    define( 'API_ACCESS_KEY', 'AAAAoyT3prI:APA91bHOXAJI-geJieyMCXeEGkJjD2tDpTAtXiV5Kgxvpe5ViMZjUwe3ts7bJ1ZZD-gCofrzUZAKjUasoefVp2yQRQpPY5psyt2vdaBG_EJjTZPl0-KeII9DZHTvuGNdGPtOnvW6W7-f');

    $sql = $this->companyOrUser($tag);
    $sth = $db->prepare($sql);
    $sth ->bindValue(":id",$id);
    $sth->execute();
    $token = $sth->fetchAll();

    $ids = array();

    foreach ($token as $o) {
        array_push($ids, $o['token']);        
    }

    $data = array(
            'body'  => $bodyMsg,
            'title' => $TittleMsg,
            'sound' => 'cancel_order',
            'tag' => 'cancel_order'
    );

    $fields = array
    (
        'registration_ids'=> $ids,
        'data'  => $data
    );


    $headers = array
    (
        'Authorization: key=' . API_ACCESS_KEY,
        'Content-Type: application/json'
    );
        #Send response To FireBase Server    
    $ch = curl_init();
    curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
    curl_setopt( $ch,CURLOPT_POST, true );
    curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
    $result = curl_exec($ch );
    curl_close( $ch );
}

function send_notification_new_scheduling($companyId, $employeeId, $bodyMsg, $TittleMsg, $db, $tag){
    define( 'API_ACCESS_KEY', 'AAAAoyT3prI:APA91bHOXAJI-geJieyMCXeEGkJjD2tDpTAtXiV5Kgxvpe5ViMZjUwe3ts7bJ1ZZD-gCofrzUZAKjUasoefVp2yQRQpPY5psyt2vdaBG_EJjTZPl0-KeII9DZHTvuGNdGPtOnvW6W7-f');

    $sql = $this->companyOrUser($tag);
    $sth = $db->prepare($sql);
    $sth->bindValue(":companyId",$companyId);
    $sth->bindValue(":employeeId",$employeeId);
    $sth->execute();
    $token = $sth->fetchAll();

    $ids = array();

    foreach ($token as $o) {
        array_push($ids, $o['token']);        
    }

    $data = array(
        'body'  => $bodyMsg,
        'title' => $TittleMsg,
        'sound' => 'order',
        'tag' => 'order'
    );

    $fields = array
    (
        'registration_ids'=> $ids,
        'data'  => $data
    );


    $headers = array
    (
        'Authorization: key=' . API_ACCESS_KEY,
        'Content-Type: application/json'
    );
        #Send response To FireBase Server    
    $ch = curl_init();
    curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
    curl_setopt( $ch,CURLOPT_POST, true );
    curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
    $result = curl_exec($ch );
    curl_close( $ch );
}

//SEND NOTIFICATION FROM FCM
function send_notification_cancel_scheduling($companyId, $employeeId, $bodyMsg, $TittleMsg, $db, $tag){
    define( 'API_ACCESS_KEY', 'AAAAoyT3prI:APA91bHOXAJI-geJieyMCXeEGkJjD2tDpTAtXiV5Kgxvpe5ViMZjUwe3ts7bJ1ZZD-gCofrzUZAKjUasoefVp2yQRQpPY5psyt2vdaBG_EJjTZPl0-KeII9DZHTvuGNdGPtOnvW6W7-f');

    $sql = $this->companyOrUser($tag);
    $sth = $db->prepare($sql);
    $sth->bindValue(":companyId",$companyId);
    $sth->bindValue(":employeeId",$employeeId);
    $sth->execute();
    $token = $sth->fetchAll();

    $ids = array();

    foreach ($token as $o) {
        array_push($ids, $o['token']);        
    }      

    $data = array(
            'body'  => $bodyMsg,
            'title' => $TittleMsg,
            'sound' => 'cancel_order',
            'tag' => 'cancel_order'
    );

    $fields = array
    (
        'registration_ids'=> $ids,
        'data'  => $data
    );


    $headers = array
    (
        'Authorization: key=' . API_ACCESS_KEY,
        'Content-Type: application/json'
    );
        #Send response To FireBase Server    
    $ch = curl_init();
    curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
    curl_setopt( $ch,CURLOPT_POST, true );
    curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
    $result = curl_exec($ch );
    curl_close( $ch );
}

function send_notification_invoice($id, $bodyMsg, $TittleMsg, $db, $tag){
    define( 'API_ACCESS_KEY', 'AAAAoyT3prI:APA91bHOXAJI-geJieyMCXeEGkJjD2tDpTAtXiV5Kgxvpe5ViMZjUwe3ts7bJ1ZZD-gCofrzUZAKjUasoefVp2yQRQpPY5psyt2vdaBG_EJjTZPl0-KeII9DZHTvuGNdGPtOnvW6W7-f');

    $sql = $this->companyOrUser($tag);
    $sth = $db->prepare($sql);
    $sth ->bindValue(":id",$id);
    $sth->execute();
    $token = $sth->fetchAll();

    $ids = array();

    foreach ($token as $o) {
        array_push($ids, $o['token']);        
    }

    $data = array(
            'body'  => $bodyMsg,
            'title' => $TittleMsg,
            'tag' => 'invoice',
    );

    $fields = array
    (
        'registration_ids'=> $ids,
        'data'  => $data
    );


    $headers = array
    (
        'Authorization: key=' . API_ACCESS_KEY,
        'Content-Type: application/json'
    );
        #Send response To FireBase Server    
    $ch = curl_init();
    curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
    curl_setopt( $ch,CURLOPT_POST, true );
    curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
    $result = curl_exec($ch );
    curl_close( $ch );
}

function send_notification_no_tag($id, $bodyMsg, $TittleMsg, $db, $tag){
    define( 'API_ACCESS_KEY', 'AAAAoyT3prI:APA91bHOXAJI-geJieyMCXeEGkJjD2tDpTAtXiV5Kgxvpe5ViMZjUwe3ts7bJ1ZZD-gCofrzUZAKjUasoefVp2yQRQpPY5psyt2vdaBG_EJjTZPl0-KeII9DZHTvuGNdGPtOnvW6W7-f');

    $sql = $this->companyOrUser($tag);
    $sth = $db->prepare($sql);
    $sth ->bindValue(":id",$id);
    $sth->execute();
    $token = $sth->fetchAll();

    $ids = array();

    foreach ($token as $o) {
        array_push($ids, $o['token']);        
    }

    $data = array(
            'body'  => $bodyMsg,
            'title' => $TittleMsg
    );

    $fields = array
    (
        'registration_ids'=> $ids,
        'notification'  => $data
    );


    $headers = array
    (
        'Authorization: key=' . API_ACCESS_KEY,
        'Content-Type: application/json'
    );
        #Send response To FireBase Server    
    $ch = curl_init();
    curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
    curl_setopt( $ch,CURLOPT_POST, true );
    curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
    $result = curl_exec($ch );
    curl_close( $ch );
}


function send_notification_chat($chat_to, $chat_from, $chat_order_id, $bodyMsg, $TittleMsg, $db, $tag){
    define( 'API_ACCESS_KEY', 'AAAAoyT3prI:APA91bHOXAJI-geJieyMCXeEGkJjD2tDpTAtXiV5Kgxvpe5ViMZjUwe3ts7bJ1ZZD-gCofrzUZAKjUasoefVp2yQRQpPY5psyt2vdaBG_EJjTZPl0-KeII9DZHTvuGNdGPtOnvW6W7-f');

    $sql = $this->companyOrUser($tag);
    $sth = $db->prepare($sql);
    $sth ->bindValue(":id",$chat_to);
    $sth->execute();
    $token = $sth->fetchAll();
    
    $ids = array();

    foreach ($token as $o) {
        array_push($ids, $o['token']);        
    }        

    $data = array(
        'body'  => $bodyMsg,
        'title' => $TittleMsg,
        'sound' => 'chat',
        'tag' => 'chat',
        'extra' => array('chat_to'=>$chat_to, 'chat_from'=> $chat_from, 'order_id'=>$chat_order_id)
    );

    $fields = array
    (
        'registration_ids'=> $ids,
        'data' => $data
    );


    $headers = array
    (
        'Authorization: key=' . API_ACCESS_KEY,
        'Content-Type: application/json'
    );
        #Send response To FireBase Server    
    $ch = curl_init();
    curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
    curl_setopt( $ch,CURLOPT_POST, true );
    curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
    $result = curl_exec($ch );
    curl_close( $ch );
}

function send_notification_coupon_available($couponId, $companyId, $db){
    define( 'API_ACCESS_KEY', 'AAAAoyT3prI:APA91bHOXAJI-geJieyMCXeEGkJjD2tDpTAtXiV5Kgxvpe5ViMZjUwe3ts7bJ1ZZD-gCofrzUZAKjUasoefVp2yQRQpPY5psyt2vdaBG_EJjTZPl0-KeII9DZHTvuGNdGPtOnvW6W7-f');

    $sql = $this->companyOrUser("coupon");
    $sth = $db->prepare($sql);
    $sth->bindValue(":couponId",$couponId);
    $sth->bindValue(":companyId",$companyId);
    $sth->execute();
    $token = $sth->fetchAll();

    $ids = array();

    foreach ($token as $o) {
        array_push($ids, $o['token']);        
    }

    $data = array(
        'body'  => "Vem cá aproveitar",
        'title' => "Chegou um cupom pra você 😍",
        'tag' => 'coupon',
    );

    $fields = array
    (
        'registration_ids'=> $ids,
        'data' => $data
    );


    $headers = array
    (
        'Authorization: key=' . API_ACCESS_KEY,
        'Content-Type: application/json'
    );
        #Send response To FireBase Server    
    $ch = curl_init();
    curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
    curl_setopt( $ch,CURLOPT_POST, true );
    curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
    $result = curl_exec($ch );
    curl_close( $ch );
}

function send_notification_reminder_scheduling($id, $bodyMsg, $TittleMsg, $db, $tag){
    define( 'API_ACCESS_KEY', 'AAAAoyT3prI:APA91bHOXAJI-geJieyMCXeEGkJjD2tDpTAtXiV5Kgxvpe5ViMZjUwe3ts7bJ1ZZD-gCofrzUZAKjUasoefVp2yQRQpPY5psyt2vdaBG_EJjTZPl0-KeII9DZHTvuGNdGPtOnvW6W7-f');

    $sql = $this->companyOrUser($tag);
    $sth = $db->prepare($sql);
    $sth ->bindValue(":id",$id);
    $sth->execute();
    $token = $sth->fetchObject();

    $ids = array();

    foreach ($token as $o) {
        array_push($ids, $o['token']);        
    }

    $data = array(
            'body'  => $bodyMsg,
            'title' => $TittleMsg,
            'tag' => 'scheduling',
    );

    $fields = array
    (
        'registration_ids'=> $ids,
        'data' => $data
    );


    $headers = array
    (
        'Authorization: key=' . API_ACCESS_KEY,
        'Content-Type: application/json'
    );
        #Send response To FireBase Server    
    $ch = curl_init();
    curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
    curl_setopt( $ch,CURLOPT_POST, true );
    curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
    $result = curl_exec($ch );
    curl_close( $ch );
}


//FUNCÃO QUE IDENTIFICA SE É PARA ENVIAR A NOTIFICAÇÃO PARA STORE OU USUARIO E RETORNA O TOKEN
function companyOrUser($tag){
            //todo new here
    switch ($tag){
        case "user":
            return "SELECT notification.notification_id as token FROM notification 
            WHERE notification.user_id = :id and notification.source = 'nuppin'";
        case "company":
            return "SELECT notification.notification_id as token FROM notification
            join employee on employee.user_id = notification.user_id AND employee.status = 'active' AND (employee.role = 'owner' or employee.role = 'admin') 
            WHERE employee.company_id = :id and notification.source = 'nuppin_company'";
        case "all_company":
            return "SELECT notification.notification_id as token FROM notification
            join employee on employee.user_id = notification.user_id AND employee.status = 'active'
            WHERE employee.company_id = :id and notification.source = 'nuppin_company'";
        case "company_scheduling":
            return "SELECT notification.notification_id as token FROM notification 
            join employee on employee.user_id = notification.user_id AND notification.source = 'nuppin_company' AND employee.status = 'active' AND (employee.role = 'owner' or employee.role = 'admin' or employee.employee_id = :employeeId)
            WHERE employee.company_id = :companyId";
        case "coupon":
            return "SELECT notification.notification_id FROM coupon_users AS uc
            JOIN company ON company.company_id = :companyId
            JOIN notification ON notification.user_id = uc.user_id and notification.source = 'nuppin'
            WHERE uc.coupon_id = :couponId and company.status = 'active' and company.visibility = 1";
    }
}

function reminderInvoicePayment(){
    $this->app->db->beginTransaction();
    try{
        $sql = "SELECT invoice.invoice_id, invoice.company_id FROM invoice
        WHERE TIMESTAMPDIFF(DAY, :hoje,invoice.due_date) = 1 AND invoice.status = :status and invoice.reminder_count = :reminderStatus";

        $sth = $this->app->db->prepare($sql);
        $sth->bindValue(":hoje",date('Y-m-d'));
        $sth->bindValue(":status",'pending');
        $sth->bindValue(":reminderStatus",0);
        $sth->execute();
        $data = $sth->fetchAll();

        if(sizeof($data)>0){
            foreach ($data as $index) {
                //todo notification
                $this->send_notification_invoice($index['company_id'], "Só um lembrete nosso para não esquecer 😉", "Sua fatura vence amanha", $this->app->db, "company");

                $sql3 = "UPDATE invoice set invoice.reminder_count = 1 where invoice.invoice_id = :invoiceId";
                $sth3 = $this->app->db->prepare($sql3);
                $sth3->bindValue(':invoiceId',$index['invoice_id']);
                $sth3->execute();           
            }
        }
        $this->app->db->commit();
    }catch(\Throwable $e){
        $this->app->db->rollBack();
    }
}

function lastReminderInvoicePayment(){
    $this->app->db->beginTransaction();
    try{
        $sql = "SELECT invoice.invoice_id, invoice.company_id FROM invoice
        WHERE TIMESTAMPDIFF(DAY, :hoje,invoice.due_date) = 0 AND invoice.status = :status and invoice.reminder_count = :reminderStatus";

        $sth = $this->app->db->prepare($sql);
        $sth->bindValue(":hoje",date('Y-m-d'));
        $sth->bindValue(":status",'pending');
        $sth->bindValue(":reminderStatus",1);
        $sth->execute();
        $data = $sth->fetchAll();

        if(sizeof($data)>0){
            foreach ($data as $index) {
                        $this->send_notification_invoice($index['company_id'], "Espero que sim, porque a fatura vai vencer hoje", "Deu tudo certo? 😬", $this->app->db, "company");

                            $sql3 = "UPDATE invoice set invoice.reminder_count = 2 where invoice.invoice_id = :invoiceId";
                            $sth3 = $this->app->db->prepare($sql3);
                            $sth3->bindValue(':invoiceId',$index['invoice_id']);
                            $sth3->execute();
            }
        }
        $this->app->db->commit();
    }catch(\Throwable $e){
        $this->app->db->rollBack();
    }
}


//CRON
function deleteTempMailSMS(){
    $sth = $this->app->db->prepare("
        SELECT temp_email.temp_email_id FROM temp_email where TIMESTAMPDIFF(MINUTE, temp_email.created_date, now()) >= 30;
        SELECT temp_sms.temp_sms_id FROM temp_sms where TIMESTAMPDIFF(MINUTE, temp_sms.created_date, now()) >= 30");
    $sth->execute();
    $email = $sth->fetchAll();
    $sth->nextRowset();
    $sms = $sth->fetchAll();

    if (sizeof($email)>0) {
        $this->app->db->beginTransaction();
        try{
            $sth2 = $this->app->db->prepare("INSERT INTO archive_temp_email_sms SELECT * FROM temp_email where temp_email.temp_email_id = :email");
            foreach ($email as $e) {

                $sth2->bindValue(':email',$e["temp_email_id"]);
                $sth2->execute();

                $sth3 = $this->app->db->prepare("DELETE from temp_email where temp_email.temp_email_id = :email");
                $sth3->bindValue(':email',$e["temp_email_id"]);
                $sth3->execute();
            }
     
            $this->app->db->commit();
        }catch(\Throwable $e){
            $this->app->db->rollBack();
        }
    }

    if (sizeof($sms)>0) {
         $this->app->db->beginTransaction();
        try{
            $sth2 = $this->app->db->prepare("INSERT INTO archive_temp_email_sms SELECT * FROM temp_sms where temp_sms.temp_sms_id = :sms");
            foreach ($sms as $s) {
                $sth2->bindValue(':sms',$s["temp_sms_id"]);
                $sth2->execute();

                $sth3 = $this->app->db->prepare("DELETE from temp_sms where temp_sms.temp_sms_id = :sms");
                $sth3->bindValue(':sms',$s["temp_sms_id"]);
                $sth3->execute();
            }
            $this->app->db->commit();
        }catch(\Throwable $e){
            $this->app->db->rollBack();
        }
    }
}


//CRON
function putOffCompanyMobile(){
    $sql = "SELECT distinct(mobile.company_id) FROM mobile
    join company on company.company_id = mobile.company_id
    where mobile.end_date is null and TIMESTAMPDIFF(HOUR, company.last_activity, now()) >= 8";
    $sth = $this->app->db->prepare($sql);
    $sth->execute();
    $mobile = $sth->fetchAll();

    $ids = array();
    foreach ($mobile as $m) {
        array_push($ids, $m['mobile_id']);        
    }

    if (sizeof($ids)>0) {
        $sql3 = "UPDATE mobile set mobile.end_date = now() where mobile.company_id = :companyId and mobile.end_date is null";
        $sth3 = $this->app->db->prepare($sql3);
        foreach ($ids as $id) {
            $sth3->bindValue(':companyId',$id);
            $sth3->execute();
        }
    }
}


//CRON
function deleteChat(){
     $sql = "SELECT distinct(chat.order_id) FROM chat
     join orders on orders.order_id = chat.order_id
     where orders.completed_date is not null and TIMESTAMPDIFF(HOUR, orders.completed_date, now()) >= 1
     ";

     $sql2 = "SELECT distinct(chat.order_id) FROM chat
     join scheduling on scheduling.scheduling_id = chat.order_id
     where scheduling.completed_date is not null and TIMESTAMPDIFF(HOUR, scheduling.completed_date, now()) >= 1
     ";

    $sth = $this->app->db->prepare($sql);
    $sth->execute();
    $order = $sth->fetchAll();
        
    $sth2 = $this->app->db->prepare($sql2);
    $sth2->execute();
    $scheduling = $sth2->fetchAll();

    $ids = array();
    foreach ($order as $r) {
        array_push($ids, $r['order_id']);        
    }
    foreach ($scheduling as $s) {
        array_push($ids, $s['order_id']);        
    }

    if (sizeof($ids)>0) {
          $this->app->db->beginTransaction();
        try{
            $sql2 = "INSERT INTO archive_chat SELECT * FROM chat where chat.order_id = :id";
            $sth2 = $this->app->db->prepare($sql2);
            foreach ($ids as $id) {
                $sth2->bindValue(':id',$id);
                $sth2->execute();
            }
            $sql3 = "DELETE from chat where chat.order_id = :id";
            $sth3 = $this->app->db->prepare($sql3);
            foreach ($ids as $id) {
                $sth3->bindValue(':id',$id);
                $sth3->execute();
            }
            $this->app->db->commit();
        }catch(\Throwable $e){
            $this->app->db->rollBack();
        }
    }
}


//CRON
function deleteMobile(){
    $this->app->db->beginTransaction();
    try{
        $sth = $this->app->db->prepare("INSERT INTO archive_mobile SELECT * FROM mobile where mobile.end_date is not null");
        $sth->execute();

        $sth2 = $this->app->db->prepare("DELETE from mobile where mobile.end_date is not null");
        $sth2->execute();

        $this->app->db->commit();
    }catch(\Throwable $e){
        $this->app->db->rollBack();
    }
}


//FUNÇÃO QUE VERIFICA O STATUS DA LOJA, DE PAGAMENTO E VENCIMENTO PARA MUDAR O STATUS DA LOJA PARA SUSPENSO SE DE ACORDO
function suspendCompany(){
        $sql = "SELECT invoice.company_id from invoice
                where invoice.due_date < :hoje and invoice.status != :paid and invoice.status != :completed and invoice.status != :free and not exists(select company.status from company  where company.company_id = invoice.company_id and company.status = :suspended)";

        $sth = $this->app->db->prepare($sql);
        $sth->bindValue(":hoje",date('Y-m-d'));
        $sth->bindValue(":suspended",'suspended');
        $sth->bindValue(":paid",'paid');
        $sth->bindValue(":completed",'completed');
        $sth->bindValue(":free",'free');
        $sth->execute();
        $data = $sth->fetchAll();

        if(sizeof($data)>0){
            $sql2 = "UPDATE company
            set company.status = :suspended
            where company.company_id = :companyId";
            $sth2 = $this->app->db->prepare($sql2);
            foreach ($data as $index) {
                $sth2->bindValue(":suspended",'suspended');
                $sth2->bindValue(":companyId",$index['company_id']);
                $sth2->execute();
            }
            $this->houston(sizeof($data)." empresas tiveram suas contas suspensas por falta de pagamento 📉");
        }
    }


//FUNÇÃO QUE GERA A FATURA AUTOMATICAMENTE
function generateInvoice($request, $response){
    $createdDate;
    $dueDate = date('Y-m-d',strtotime("+5 days", strtotime(date('Y-m-d'))));
    $createdDate = date('Y-m-d');

    if (date('N', strtotime($dueDate)) >= 6) {
        if (date('N', strtotime($dueDate)) == 6) {
            $dueDate = date('Y-m-d',strtotime("+7 days", strtotime(date('Y-m-d'))));
        }else{
            $dueDate = date('Y-m-d',strtotime("+6 days", strtotime(date('Y-m-d'))));
        }
    }

    $this->app->db->beginTransaction();
    try{
        //todo new here

        $sql = "SELECT 
        company.company_id, 
        company.category_company_id, 
        users.email, 
        users.full_name, 
        company.name, 
        company.document_number, 
        company.document_type, 
        company.status, 
        pc.price, 
        pc.fee, 
        pc.trial_period, 
        pc.trial_price, 
        pc.trial_fee, 
        invoice.created_date as last_month, 
        TIMESTAMPDIFF(MONTH, pc.created_date, :hoje) as trial
        FROM company
        LEFT JOIN invoice ON invoice.invoice_id = (SELECT invoice2.invoice_id FROM invoice as invoice2 WHERE invoice2.company_id = company.company_id order by invoice2.created_date desc limit 1)
        join employee on employee.company_id = company.company_id and employee.role = 'owner' and employee.status = 'active'
        join users on users.user_id = employee.user_id
        join plan_company as pc on pc.company_id = company.company_id
        WHERE (TIMESTAMPDIFF(MONTH, invoice.completed_date, :hoje) >= 1  or invoice.created_date IS null) and company.status!=:suspended group by employee.company_id";

        $sth = $this->app->db->prepare($sql);
        $sth->bindValue(':hoje',$createdDate);
        $sth->bindValue(':suspended','suspended');
        $sth->execute();
        $data = $sth->fetchAll();
        //return $response->withJson($data);


        if(sizeof($data) != 0){
                $sth2 = $this->app->db->prepare("INSERT into invoice(invoice_id, company_id, due_date, created_date, subtotal_amount, fee_amount, total_amount, status, completed_date) 
                    values (:invoice_id, :company_id, :due_date, :created_date, :subtotal_amount, :fee_amount, :total_amount, :status, :completed_date)");
                foreach ($data as $index) {
                    $invoiceId = $this->uniqidReal(9);
                    $sth2->bindValue(':invoice_id',$invoiceId);
                    $sth2->bindValue(':company_id',$index['company_id']);
                    $sth2->bindValue(':due_date',$dueDate);
                    $sth2->bindValue(':created_date', $createdDate);
                    $sth2->bindValue(':completed_date', null);
                    $sth2->bindValue(':status', "pending");

                    $fee;
                    if($index['trial'] >= $index['trial_period'] || $index['trial_period'] == 0){
                        $fee = $this->geraComissao($index['company_id'],$index['category_company_id'], $createdDate, $index['last_month'], $index['fee']);
                    }else{
                        $fee = $this->geraComissao($index['company_id'],$index['category_company_id'], $createdDate, $index['last_month'], $index['trial_fee']);
                    }

                    $sth2->bindValue(':fee_amount',$fee);
                    
                    if(($index['trial'] >= $index['trial_period'] || $index['trial_period'] == 0) && $index['price']+$fee >= 9){

                        $sth2->bindValue(':subtotal_amount',$index['price']);
                        $sth2->bindValue(':total_amount',$index['price']+$fee);
                        $sth2->execute();
                        $this->generateInvoiceBoleto($index['document_number_type'], $index['price'], $fee, $index['email'], $index['full_name'], $index['document_number'], $index['name'], $invoiceId, $index['company_id']);    

                    }else if($index['trial'] < $index['trial_period'] && $index['trial_price']+$fee >= 9){

                        $sth2->bindValue(':subtotal_amount',$index['trial_price']);
                        $sth2->bindValue(':total_amount',$index['trial_price']+$fee);
                        $sth2->execute();
                        $this->generateInvoiceBoleto($index['document_number_type'], $index['trial_price'], $fee, $index['email'], $index['full_name'], $index['document_number'], $index['name'], $invoiceId,$index['company_id']);

                    }else{
                        $sth2->bindValue(':subtotal_amount',0);
                        $sth2->bindValue(':total_amount',$fee);
                        $this->generateInvoiceFree($sth2, $createdDate, $index['status'], $index['company_id']);
                    }

                }
                $this->app->db->commit();
                $this->mrGoodNews(sizeof($data)." faturas geradas 📃");
            }
    }catch(\Throwable $e){
        return $response->withJson(false)->withStatus(500);
        $this->app->db->rollBack();
    }
}

function generateInvoiceFree($sth2, $createdDate, $companyStatus, $companyId){
    $sth2->bindValue(':status','free');
    $sth2->bindValue(':total_amount',0);
    $sth2->bindValue(':completed_date',$createdDate);
    $sth2->execute();

    if ($companyStatus != "active") {
        $sql="UPDATE company set company.status = :status where company.company_id = :companyId";
        $sth = $this->app->db->prepare($sql);
        $sth->bindValue(':status',"active");
        $sth->bindValue(':companyId',$companyId);
        $sth->execute();
    }
}

function generateInvoiceBoleto($documentType, $price, $fee, $email, $fullName, $documentNumber, $companyName, $invoiceId, $companyId){
    $boleto;
    if ($documentType == 'CPF') {
        $boleto = json_decode($this->bolete_function(round($price * 100), $fee * 100, $email,$fullName,$documentNumber,$invoiceId));
    }else{
        $boleto = json_decode($this->bolete_function(round($price * 100), $fee * 100, $email,$companyName,$documentNumber,$invoiceId));
    }

    try{
        $sql2 ="INSERT into invoice_payment(invoice_payment_id, invoice_id, status, amount, created_date, due_date, code_line, external_link, completed_date) 
        values (:invoice_payment_id, :invoice_id, :status, :amount, :created_date, :due_date, :code_line, :external_link, :completed_date)";
        $sth2 = $this->app->db->prepare($sql2);
        $sth2->bindValue(':invoice_payment_id',$boleto->create_request->transaction_id);
        $sth2->bindValue(':invoice_id',$invoiceId);
        $sth2->bindValue(':status',$boleto->create_request->status);
        $sth2->bindValue(':amount',$boleto->create_request->value_cents/100);
        $sth2->bindValue(':created_date',$boleto->create_request->created_date);
        $sth2->bindValue(':due_date',$boleto->create_request->due_date);
        $sth2->bindValue(':code_line',$boleto->create_request->bank_slip->digitable_line);
        $sth2->bindValue(':external_link',$boleto->create_request->bank_slip->url_slip_pdf);
        $sth2->bindValue(':completed_date',null);

         //se der algum erro com o boleto ele não gera a fatura
        if ($boleto->create_request->transaction_id != null) {
            $sth2->execute();
            $sql="UPDATE invoice set invoice.status = :status where invoice.invoice_id = :invoiceId";
            $sth = $this->app->db->prepare($sql);
            $sth->bindValue(':status',$boleto->create_request->status);
            $sth->bindValue(':invoiceId',$invoiceId);
            $sth->execute();
            $this->send_notification_invoice($companyId, "Só entrar no app e pegar o codigo do seu boleto", "Sua fatura está pronta! 😃", $this->app->db, "company");     
        }else{
            $sth = $this->app->db->prepare("DELETE from invoice where invoice.invoice_id = :invoiceId");
            $sth->bindValue(':invoiceId',$invoiceId);
            $sth->execute();
        }
    }catch(\Throwable $e){
        throw new \Exception('error. boleto');
    }
}


//FUNÇÃO QUE CRIAR O BOLETO NO PAGHIPER
function bolete_function($price, $fee,$email,$nome,$cpfCnpj,$invoiceId){
    $itens = array(
                    array (
                        'item_id'=>'pla01',
                        'description'=>'Plano Mensal',
                        'quantity'=>1,
                        'price_cents'=>$price
                    ),
                    array (
                        'item_id'=>'fee01',
                        'description'=>'Taxa',
                        'quantity'=>1,
                        'price_cents'=>$fee
                    )
            );

    $fields = array
            (
                'apiKey'=>'apk_49722569-EVgXGKLbBUROQvYaBFzZBxKUSRpTjGRo',
                'order_id'=>$invoiceId,
                'payer_email' => $email,
                'payer_name' => $nome,
                'payer_cpf_cnpj'=> $cpfCnpj,
                'days_due_date'=> 5,
                'type_bank_slip'=> "boletoA4",
                'items'=>$itens
            );


    $headers = array
            (
                'Content-Type: application/json'
            );
    #Send response To FireBase Server    
    $ch = curl_init();
    curl_setopt( $ch,CURLOPT_URL, 'https://api.paghiper.com/transaction/create');
    curl_setopt( $ch,CURLOPT_POST, true );
    curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
    $result = curl_exec($ch );
    curl_close( $ch );
    return $result;
}


//FUNÇÃO QUE GERA A COMISSÃO DO MÊS
function geraComissao($companyId, $category, $invoiceCreatedDate, $invoiceLastCreatedDate, $fee){
    $sql;
    if ($invoiceLastCreatedDate == null) {
    
        return 0;

        }else{
            if($category == 3){
            $sql = "SELECT if((SUM(scheduling.total_amount)*:fee) IS NULL,0,(SUM(scheduling.total_amount)*:fee)) as total from scheduling
            where scheduling.company_id = :id and DATE_FORMAT(scheduling.completed_date, '%Y-%m-%d') >= :invoiceLastCreatedDate and  DATE_FORMAT(scheduling.completed_date, '%Y-%m-%d') < :invoiceCreatedDate and scheduling.status LIKE :concluded and scheduling.source = :nuppin";
        }else if($category == 1 || $category == 2){
            $sql = 
            "SELECT if((SUM(orders.total_amount)*:fee) IS NULL,0,(SUM(orders.total_amount)*:fee)) as total from orders
            where orders.company_id = :id and DATE_FORMAT(orders.completed_date, '%Y-%m-%d') >= :invoiceLastCreatedDate and DATE_FORMAT(orders.completed_date, '%Y-%m-%d') <:invoiceCreatedDate and orders.status LIKE :concluded and orders.source = :nuppin";
        }
        $sth = $this->app->db->prepare($sql);
        $sth->bindValue(":id",$companyId);
        $sth->bindValue(":invoiceCreatedDate",$invoiceCreatedDate);
        $sth->bindValue(":invoiceLastCreatedDate",$invoiceLastCreatedDate);
        $sth->bindValue(":fee",$fee);
        $sth->bindValue(":nuppin","nuppin");
        $sth->bindValue(":concluded", "%concluded%");
        $sth->execute();
        $data2 = $sth->fetchObject();

        return $data2->total;
        }
}

    function templateEmailCode($code){
        return '<div style="margin: 0 auto; padding: 0; max-width:600px;" width="100%">
        <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
        <td bgcolor="#ffffff"align="center"style="padding: 20px 0 20px 0; text-align: center;">
        <b style="color:#ff585d; font-size:24px";>Nuppin</b>
        </td>
        </tr>
        <tr bgcolor="#ffffff" >
        <td bgcolor="#ffffff" style="color:#153643; font-family: Arial, sans-serif; text-align:center;font-size:18px;margin:5px 60px 30px;display:block">
        <b>Seu codigo de acesso é:</b>
        </td>
        </tr>
        <tr bgcolor="#ffffff">
        <td style="padding: 0 20px 0 20px;">
        <table style="min-width:340px;" width="100%" height="70px" cellspacing="0" cellpadding="0" border="0"> 
        <tbody>
        <tr> 
        <td style="border-radius:4px;text-align:center" bgcolor="#ffbeba" align="center"> 
        <span style="text-align:center;font-size:36px;font-weight:bold;color:#3f3e3e;letter-spacing:20px"> '.strtoupper($code).'
        </span> 
        </td> 
        </tr> 
        </tbody>
        </table>
        </td>
        </tr>
        <tr>
        <td bgcolor="#ffffff"align="center" style="padding: 40px 0 10px 0;">
        <table border="0" cellpadding="0" cellspacing="0">
        <tr>
        <td>
        <a href="http://www.instagram.com/nuppinbr">
        <img src="https://nuppin-img.s3.us-east-2.amazonaws.com/icons/iconInsta.png" alt="Instagram" width="38" height="38" style="display: block;" border="0" />
        </a>
        </td>
        <td style="font-size: 0; line-height: 0;" width="20">&nbsp;</td>
        <td>
        <a href="http://www.facebook.com/nuppinbr">
        <img src="https://nuppin-img.s3.us-east-2.amazonaws.com/icons/iconFace.png" alt="Facebook" width="38" height="38" style="display: block;" border="0" />
        </a>
        </td>
        </tr>
        </table>
        </td>
        </tr>
        <tr>
        <td bgcolor="#ffffff"style="padding: 30px 30px 0 30px;text-align:center; color: #a6a29f; font-family: Arial, sans-serif; font-size: 14px;">
        &reg; Nuppin - Todos os direitos reservados 2020<br/>
        Esse é um email automático.
        </td>
        </td>
        </tr>
        </table>
        </div>';
    }


    function addCollection($request, $response){
    $inputCollection = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputCollection['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $keys = array_keys($inputCollection); 
    $collectionId;

    if($inputCollection["external_code"] != ""){
        $sth2 = $this->app->db->prepare("SELECT collection.collection_id FROM collection WHERE collection.external_code = :externalId and collection.company_id = :companyId");
        $sth2->bindParam("externalId", $inputCollection['external_id']);
        $sth2->bindParam("companyId", $inputCollection['company_id']);
        $sth2->execute();
        $check = $sth2->fetchObject();

        if ($check) {
            $error = array();
            $error['error_code'] = "001";
            $error['error_message'] = "Você já criou um grupo com esse codigo de referência";
            $arrayJson = array("error" => $error, "collection" => false);
            return $response->withJson($arrayJson);
        }
    }


    $sql = "INSERT INTO collection (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")";
    $sth = $this->app->db->prepare($sql);
    foreach ($inputCollection as $key => $value) {
        if($key == "collection_id"){
            $collectionId = $this->uniqidReal(9);
            $sth ->bindValue(":".$key,$collectionId);
        }else{
            $sth ->bindValue(':'.$key,$value);
        }
    }
    $sth->execute();
    $inputCollection['collection_id'] = $collectionId;
    $arrayJson = array("collection" => $inputCollection);

    $this->mrGoodNews("Coleção adicionada 🗃");

    return $response->withJson($arrayJson);
}

function updateCollection($request,$response,$args){
    $inputCollection = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputCollection['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sets = [];

    if($inputCollection["external_code"] != ""){
        $sth2 = $this->app->db->prepare("SELECT colection.collection_id FROM collection WHERE collection.external_code = :externalId and collection.company_id = :companyId and collection.collection_id != :collectionId");
        $sth2->bindParam("externalId", $inputCollection['external_code']);
        $sth2->bindParam("companyId", $inputCollection['company_id']);
        $sth2->bindParam("collectionId", $inputCollection['collection_id']);
        $sth2->execute();
        $check = $sth2->fetchObject();

        if ($check) {
            $error = array();
            $error['error_code'] = "001";
            $error['error_message'] = "Já existe um conjunto seu com esse codigo de referencia";
            $arrayJson = array("error" => $error, "collection" => false);
            return $response->withJson($arrayJson);
        }
    }

    foreach ($inputCollection as $key => $VALUES) {
        $sets[] = $key." = :".$key;
    }   
    $sql = "UPDATE collection SET ".implode(',', $sets)." WHERE collection.collection_id = :id";
    $sth = $this->app->db->prepare($sql);
    $sth->bindValue(':id',$inputCollection['collection_id']);
    foreach ($inputCollection as $key => $value) {
        $sth->bindValue(':'.$key,$value);
    }
    $sth->execute();
    $arrayJson = array("collection" => $inputCollection);
    return $response->withJson($arrayJson);
}

function deleteCollection($request,$response,$args){
    $inputCollection = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputCollection['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $this->app->db->beginTransaction();
    try{
        $sth = $this->app->db->prepare("DELETE FROM product_collection as pc where pc.collection_id = :collectionId");
        $sth->bindParam(":collectionId", $inputCollection['collection_id']);
        $sth->execute();

        $sth2 = $this->app->db->prepare("DELETE FROM collection_extra as ce WHERE ce.collection_id = :collectionId");
        $sth2->bindParam(":collectionId", $inputCollection['collection_id']);
        $sth2->execute();

        $sth2 = $this->app->db->prepare("DELETE FROM collection WHERE collection.collection_id = :collectionId");
        $sth2->bindParam(":collectionId", $inputCollection['collection_id']);
        $sth2->execute();

        $this->app->db->commit();
        return $response->withJson(1);
    }catch(\Throwable $e){
        $this->app->db->rollBack();
        return $response->withJson(false);
    }
}


function getListCollection($request,$response,$args){
    $token = $request->getAttribute("token");

    if (!($args['companyId'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("
        SELECT collection.*,
        if(collection.min_quantity<=(select count(*) from collection_extra as ce where ce.collection_id = collection.collection_id),0,1) as has_warning,
        if((select count(*) from collection_extra as ce2 where ce2.collection_id = collection.collection_id) = 0,1,0) as is_empty
        FROM collection WHERE collection.company_id = :companyId ORDER BY collection.created_date");
    $sth->bindParam("companyId", $args['companyId']);
    $sth->execute();
    $collection = $sth->fetchAll();
    return $response->withJson($collection);
}

function getListCollectionNotProduct($request,$response,$args){
    $token = $request->getAttribute("token");

    if (!($args['companyId'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("
        SELECT * FROM collection WHERE collection.company_id = :companyId and
        not exists (select * from product_collection as pc where pc.collection_id = collection.collection_id and product_id = :productId) and exists (select * from collection_extra as ce where ce.collection_id = collection.collection_id) and collection.min_quantity <= (select count(*) from collection_extra as ce2 where ce2.collection_id = collection.collection_id)
        ORDER BY collection.created_date");
    $sth->bindParam(":companyId", $args['companyId']);
    $sth->bindParam(":productId", $args['productId']);
    $sth->execute();
    $collection = $sth->fetchAll();
    return $response->withJson($collection);
}

function getListExtraNotCollection($request,$response,$args){
    $token = $request->getAttribute("token");

    if (!($args['companyId'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("
        SELECT * FROM extra WHERE extra.company_id = :companyId and
    not exists (select * from collection_extra as ce where ce.collection_id = :collectionId and ce.extra_id = extra.extra_id)");
    $sth->bindParam(":collectionId", $args['collectionId']);
    $sth->bindParam(":companyId", $args['companyId']);
    $sth->execute();
    $extra = $sth->fetchAll();
    return $response->withJson($extra);
}

function getCollection($request,$response,$args){
    $token = $request->getAttribute("token");

    $sth = $this->app->db->prepare("
        SELECT collection.*,
        if(collection.min_quantity<=(select count(*) from collection_extra as ce where ce.collection_id = collection.collection_id),0,1) as has_warning
        FROM collection WHERE collection.collection_id = :collectionId;
        
        SELECT extra.*, ce.collection_id from extra
        join collection_extra as ce on ce.collection_id = :collectionId and extra.extra_id = ce.extra_id;");

    $sth->bindValue("collectionId", $args['id']);
    $sth->execute();
    $collection = $sth->fetchObject();
    $sth->nextRowset();
    $extra = $sth->fetchAll();

    //todo token
    if (!($collection->company_id == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }
    
    $arrayJson = array(
        "collection" => $collection,
        "extra" => $extra
        );
    return $response->withJson($arrayJson);
}

function addCollectionExtra($request, $response){
    $inputCollection = $request->getParsedBody();
    $token = $request->getAttribute("token");

    //TODO TOKEN HERE
    if (!($inputCollection[0]["company_id"] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    foreach ($inputCollection as $key => $value) {
        $keys = array_keys($value); 

        $sql = "INSERT INTO collection_extra (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")";
        $sth = $this->app->db->prepare($sql);
        foreach ($value as $key1 => $value1) {
            $sth ->bindValue(':'.$key1,$value1);
        }
        $sth->execute();
    }

    $this->mrGoodNews(sizeof($inputCollection)." extra adicionado na coleção 📄🧲🗃");

    return $response->withJson($inputCollection);
}

function deleteCollectionExtra($request,$response){
    $inputCollection = $request->getParsedBody();
    $token = $request->getAttribute("token");
    //todo token here

    if (!($inputCollection["company_id"] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }
    
    $sth = $this->app->db->prepare("DELETE FROM collection_extra as ce WHERE ce.collection_id = :collectionId and ce.extra_id = :extraId");
    $sth->bindParam(":collectionId", $inputCollection['collection_id']);
    $sth->bindParam(":extraId", $inputCollection['extra_id']);
    $sth->execute();
    $affectedRows = $sth->rowCount();
    return $response->withJson($affectedRows);
}

function deleteCollectionProduct($request,$response){
    $inputCollection = $request->getParsedBody();
    $token = $request->getAttribute("token");

    //todo token here
    if (!($inputCollection["company_id"] == $token["comp"])) {
        return $response->withJson($inputCollection["company_id"])->withStatus(403);
    }
    
    $sth = $this->app->db->prepare("DELETE FROM product_collection as pc WHERE pc.collection_id = :collectionId and pc.product_id = :productId");
    $sth->bindParam(":collectionId", $inputCollection['collection_id']);
    $sth->bindParam(":productId", $inputCollection['product_id']);
    $sth->execute();
    $affectedRows = $sth->rowCount();
    return $response->withJson($affectedRows);
}


  function addExtra($request, $response){
    $inputExtra = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputExtra['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $keys = array_keys($inputExtra); 
    $extraId;

    if($inputExtra["external_code"] != ""){
        $sth2 = $this->app->db->prepare("SELECT extra.extra_id FROM extra WHERE extra.external_code = :externalId and extra.company_id = :companyId");
        $sth2->bindParam("externalId", $inputExtra['external_code']);
        $sth2->bindParam("companyId", $inputExtra['company_id']);
        $sth2->execute();
        $check = $sth2->fetchObject();

        if ($check) {
            $error = array();
            $error['error_code'] = "001";
            $error['error_message'] = "Já existe um item seu com esse codigo de referencia";
            $arrayJson = array("error" => $error, "extra" => false);
            return $response->withJson($arrayJson);
        }
    }


    $sql = "INSERT INTO extra (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")";
    $sth = $this->app->db->prepare($sql);
    foreach ($inputExtra as $key => $value) {
        if($key == "extra_id"){
            $extraId = $this->uniqidReal(9);
            $sth ->bindValue(":".$key,$extraId);
        }else{
            $sth ->bindValue(':'.$key,$value);
        }
    }
    $sth->execute();
    $inputExtra['extra_id'] = $extraId;
    $arrayJson = array("extra" => $inputExtra);

    $this->mrGoodNews("Extra adicionado 📄");

    return $response->withJson($arrayJson);
}

function updateExtra($request,$response,$args){
    $inputExtra = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputExtra['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sets = [];

    if($inputExtra["external_code"] != ""){
        $sth2 = $this->app->db->prepare("SELECT extra.extra_id FROM extra WHERE extra.external_code = :externalId and extra.company_id = :companyId and extra.extra_id != :extraId");
        $sth2->bindParam("externalId", $inputExtra['external_code']);
        $sth2->bindParam("companyId", $inputExtra['company_id']);
        $sth2->bindParam("extraId", $inputExtra['extra_id']);
        $sth2->execute();
        $check = $sth2->fetchObject();

        if ($check) {
            $error = array();
            $error['error_code'] = "001";
            $error['error_message'] = "Já existe um item seu com esse codigo de referencia";
            $arrayJson = array("error" => $error, "collection" => false);
            return $response->withJson($arrayJson);
        }
    }

    foreach ($inputExtra as $key => $VALUES) {
        $sets[] = $key." = :".$key;
    }   
    $sql = "UPDATE extra SET ".implode(',', $sets)." WHERE extra.extra_id = :id";
    $sth = $this->app->db->prepare($sql);
    $sth->bindValue(':id',$inputExtra['extra_id']);
    foreach ($inputExtra as $key => $value) {
        $sth->bindValue(':'.$key,$value);
    }
    $sth->execute();
    $arrayJson = array("extra" => $inputExtra);
    return $response->withJson($arrayJson);
}

function deleteExtra($request,$response,$args){
    $inputExtra = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputExtra['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $this->app->db->beginTransaction();
    try{
        $sth = $this->app->db->prepare("DELETE FROM collection_extra as ce where ce.extra_id = :extra");
        $sth->bindParam(":extra", $inputExtra['extra_id']);
        $sth->execute();

        $sth2 = $this->app->db->prepare("DELETE FROM extra WHERE extra.extra_id = :extra");
        $sth2->bindParam(":extra", $inputExtra['extra_id']);
        $sth2->execute();

        $this->app->db->commit();
        return $response->withJson(1);
    }catch(\Throwable $e){
        $this->app->db->rollBack();
        return $response->withJson(false);
    }
}

function getListExtra($request,$response,$args){
    $token = $request->getAttribute("token");

    if (!($args['companyId'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("
        SELECT * FROM extra WHERE extra.company_id = :companyId ORDER BY extra.created_date");
    $sth->bindParam("companyId", $args['companyId']);
    $sth->execute();
    $product = $sth->fetchAll();
    return $response->withJson($product);
}

function getExtra($request,$response,$args){
    //TODO TOKEN HERE
    $sth = $this->app->db->prepare("
        SELECT * FROM extra WHERE extra.extra_id = :extraId;");
    $sth->bindValue("extraId", $args['id']);
    $sth->execute();
    $collection = $sth->fetchObject();
    $sth->nextRowset();
    $extra = $sth->fetchAll();
    
    $arrayJson = array(
        "extra" => $extra
        );
    return $response->withJson($arrayJson);
}

function addProductCollection($request, $response){
    $input = $request->getParsedBody();
    $token = $request->getAttribute("token");

    //TODO TOKEN HERE
    if (!($input[0]["company_id"] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    foreach ($input as $key => $value) {
        $keys = array_keys($value); 

        $sql = "INSERT INTO product_collection (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")";
        $sth = $this->app->db->prepare($sql);
        foreach ($value as $key1 => $value1) {
            $sth ->bindValue(':'.$key1,$value1);
        }
        $sth->execute();
    }

    $this->mrGoodNews(sizeof($input)." coleção adicionada no produto 🗃🧲👕");

    return $response->withJson($input);
}

function addSize($request, $response){
    $inputSize = $request->getParsedBody();
    $token = $request->getAttribute("token");

    //TODO TOKEN HERE
     if (!($inputSize["company_id"] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $keys = array_keys($inputSize); 

    $sth2 = $this->app->db->prepare("SELECT size.size_id FROM size WHERE size.name = :name and size.product_id = :productId");
    $sth2->bindValue(':name', $inputSize['name']);
    $sth2->bindValue(':productId', $inputSize['product_id']);
    $sth2->execute();
    $sizeExist = $sth2->fetchObject();
    if($sizeExist){
        $error = array();
        $error['error_code'] = "001";
        $error['error_message'] = "Não é possivel criar, pois esse tamanho já existe nesse produto!";
        $arrayJson = array("size" => false, "error" => $error);
        return $response->withJson($arrayJson);
    }

    $this->app->db->beginTransaction();
    try{
        $sql = "INSERT INTO size (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")";
        $sth = $this->app->db->prepare($sql);
        foreach ($inputSize as $key1 => $value1) {
            if($key1 == "size_id"){
                $sth->bindValue(":".$key1,$this->uniqidReal(9));
            }else{
                $sth->bindValue(':'.$key1,$value1);
            }
        }
        $sth->execute();


        $sth2 = $this->app->db->prepare("UPDATE product set product.multi_stock_quantity = product.multi_stock_quantity + :quantity WHERE product.product_id = :productId");
        $sth2->bindValue(':quantity',$inputSize['stock_quantity']);
        $sth2->bindValue(':productId',$inputSize['product_id']);
        $sth2->execute();

        $this->app->db->commit();

        $this->mrGoodNews("Tamanho criado para um produto 🏷");

        $arrayJson = array("size" => $inputSize);
        return $response->withJson($arrayJson);
    }catch(\Throwable $e){
        $this->app->db->rollBack();
        return $response->withJson(false);
    }
}

function updateSize($request,$response,$args){
    $inputSize = $request->getParsedBody();
    $token = $request->getAttribute("token");

    //TODO TOKEN HERE
     if (!($inputSize["company_id"] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $sets = [];

    $sth4 = $this->app->db->prepare("SELECT size.stock_quantity FROM size WHERE size.size_id = :sizeId and size.product_id = :productId");
    $sth4->bindParam("sizeId", $inputSize['size_id']);
    $sth4->bindParam("productId", $inputSize['product_id']);
    $sth4->execute();
    $product = $sth4->fetchObject();

    foreach ($inputSize as $key => $VALUES) {
        $sets[] = $key." = :".$key;
    }   
    $sql = "UPDATE size SET ".implode(',', $sets)." WHERE size.product_id = :productId and size.size_id = :sizeId";
    $sth = $this->app->db->prepare($sql);
    $sth->bindValue(':productId',$inputSize['product_id']);
    $sth->bindValue(':sizeId',$inputSize['size_id']);
    foreach ($inputSize as $key => $value) {
        $sth->bindValue(':'.$key,$value);
    }
    $sth->execute();

    if($product->stock_quantity < $inputSize['stock_quantity']){
        $updateStock = $inputSize['stock_quantity'] - $product->stock_quantity;
        $sql = "UPDATE product set product.multi_stock_quantity = product.multi_stock_quantity + :psStock where product.product_id = :productId";
    }else{
        $updateStock = $product->stock_quantity - $inputSize['stock_quantity'];
        $sql = "UPDATE product set product.multi_stock_quantity = product.multi_stock_quantity - :psStock where product.product_id = :productId";
    }

    $sth1 = $this->app->db->prepare($sql);
    $sth1->bindParam(":productId", $inputSize['product_id']);
    $sth1->bindParam(":psStock", $updateStock);
    $sth1->execute();

    $arrayJson = array("size" => $inputSize);
    return $response->withJson($arrayJson);
}

function deleteSize($request,$response,$args){
    $inputSize = $request->getParsedBody();
    $token = $request->getAttribute("token");

    //TODO TOKEN HERE
     if (!($inputSize["company_id"] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $this->app->db->beginTransaction();
    try{

        $sth = $this->app->db->prepare("DELETE FROM size where size.size_id = :sizeId and size.product_id = :productId");
        $sth->bindParam(":sizeId", $inputSize['size_id']);
        $sth->bindParam(":productId", $inputSize['product_id']);
        $sth->execute();

        $sth1 = $this->app->db->prepare("UPDATE product set product.multi_stock_quantity = product.multi_stock_quantity - :psStock where product.product_id = :productId");
        $sth1->bindParam(":productId", $inputSize['product_id']);
        $sth1->bindParam(":psStock", $inputSize['stock_quantity']);
        $sth1->execute();
        
        $this->app->db->commit();
        return $response->withJson(1);
    }catch(\Throwable $e){
        $this->app->db->rollBack();
        return $response->withJson(false);
    }
}

function decrementProductStock($products){
    foreach($products as $value){

        $sthStock = $this->app->db->prepare("SELECT product.is_stock, product.is_multi_stock FROM product WHERE product.product_id = :item");
        $sthStock->bindValue(':item',$value['product_id']);
        $sthStock->execute();
        $product = $sthStock->fetchObject();

        if ($product->is_stock == 1) {
            $sthStock = $this->app->db->prepare("UPDATE product SET product.stock_quantity = (product.stock_quantity - :quantity) where product.product_id = :item");
            $sthStock ->bindValue(':quantity',$value['quantity']);
            $sthStock ->bindValue(':item',$value['product_id']);
            $sthStock->execute();
        }else if($product->is_multi_stock == 1){
            $sql = "UPDATE size SET size.stock_quantity = size.stock_quantity - :quantity WHERE size.product_id = :productId and size.size_id = :sizeId";
            $sth = $this->app->db->prepare($sql);
            $sth->bindValue(':productId',$value["product_id"]);
            $sth->bindValue(':sizeId',$value["size_id"]);
            $sth->bindValue(':quantity',$value["quantity"]);
            $sth->execute();

            $sth1 = $this->app->db->prepare("UPDATE product set product.multi_stock_quantity = product.multi_stock_quantity - :quantity where product.product_id = :productId");
            $sth1->bindParam(":productId", $value["product_id"]);
            $sth1->bindParam(":quantity", $value["quantity"]);
            $sth1->execute();
        }
    }
}

function incrementProductStock($orderId){
 
    $sql = "SELECT order_item.product_id, order_item.quantity, order_item.size_id FROM order_item WHERE order_item.order_id = :orderId";
    $sth = $this->app->db->prepare($sql);
    $sth->bindValue(':orderId', $orderId);
    $sth->execute();
    $orderItem = $sth->fetchAll();

    foreach($orderItem as $value){
        $sthStock = $this->app->db->prepare("SELECT product.is_stock, product.is_multi_stock FROM product WHERE product.product_id = :item");
        $sthStock->bindValue(':item',$value['product_id']);
        $sthStock->execute();
        $product = $sthStock->fetchObject();

        if ($product->is_stock == 1) {
            $sthStock = $this->app->db->prepare("UPDATE product SET product.stock_quantity = (product.stock_quantity + :quantity) where product.product_id = :item");
            $sthStock ->bindValue(':quantity',$value['quantity']);
            $sthStock ->bindValue(':item',$value['product_id']);
            $sthStock->execute();

        }else if($product->is_multi_stock == 1){
            $sql = "UPDATE size SET size.stock_quantity = size.stock_quantity + :quantity WHERE size.product_id = :productId and size.size_id = :sizeId";
            $sth = $this->app->db->prepare($sql);
            $sth->bindValue(':productId',$value["product_id"]);
            $sth->bindValue(':sizeId',$value["size_id"]);
            $sth->bindValue(':quantity',$value["quantity"]);
            $sth->execute();

            $sth1 = $this->app->db->prepare("UPDATE product set product.multi_stock_quantity = product.multi_stock_quantity + :quantity where product.product_id = :productId");
            $sth1->bindParam(":productId", $value["product_id"]);
            $sth1->bindParam(":quantity", $value["quantity"]);
            $sth1->execute();
        }
    }
}

function updateProductPosition($request, $response){
    $inputProduct = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputProduct[0]['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    foreach ($inputProduct as $key => $value) {
        $sql = "UPDATE product set product.position = :productPosition where product.product_id = :productId";
        $sth = $this->app->db->prepare($sql);
        $sth->bindValue(':productPosition',$value["position"]);
        $sth->bindValue(':productId',$value["product_id"]);
        $sth->execute();
    }
     $this->mrGoodNews("Produtos reorganizados 🔄");

    return $response->withJson($inputProduct);
}

function updateServicePosition($request, $response){
    $inputService = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputService[0]['company_id'] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }


    foreach ($inputService as $key => $value) {
        $sql = "UPDATE service set service.position = :servicePosition where service.service_id = :serviceId";
        $sth = $this->app->db->prepare($sql);
        $sth->bindValue(':servicePosition',$value["position"]);
        $sth->bindValue(':serviceId',$value["service_id"]);
        $sth->execute();
    }
    $this->mrGoodNews("Serviços reorganizados 🔄");

    return $response->withJson($inputService);
}

function updateProductCollectionPosition($request, $response){
    $inputCollection = $request->getParsedBody();
    $token = $request->getAttribute("token");

    //TODO TOKEN HERE
    if (!($inputCollection[0]["company_id"] == $token["comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    foreach ($inputCollection as $key => $value) {
        $sql = "UPDATE product_collection as pc set pc.position = :pcPosition where pc.product_id = :productId and pc.collection_id = :collectionId";
        $sth = $this->app->db->prepare($sql);
        $sth->bindValue(':pcPosition',$value["position"]);
        $sth->bindValue(':productId',$value["product_id"]);
        $sth->bindValue(':collectionId',$value["collection_id"]);
        $sth->execute();
    }
    $this->mrGoodNews("Coleção reorganizada 🔄");

    return $response->withJson($inputCollection);
}

//CRON
function reminderSchedulingDay(){

    $this->app->db->beginTransaction();
    try{
        $sql = "SELECT scheduling.scheduling_id, scheduling.user_id, scheduling.company_name, scheduling.user_name FROM scheduling
        WHERE TIMESTAMPDIFF(HOUR, :hoje, scheduling.start_time) <= 24 AND scheduling.status = :status and scheduling.reminder_count = :reminderStatus and scheduling.source = :nuppin";

        $sth = $this->app->db->prepare($sql);
        $sth->bindValue(":hoje",date('Y-m-d H:i:s'));
        $sth->bindValue(":status",'accepted');
        $sth->bindValue(":reminderStatus",0);
        $sth->bindValue(":nuppin","nuppin");
        $sth->execute();
        $data = $sth->fetchAll();

        if(sizeof($data)>0){
            foreach ($data as $scheduling) {
                //todo notification
                $this->send_notification_reminder_scheduling($scheduling['user_id'], "É amanhã seu agendamento na ".$scheduling['company_name'], "Só um lembrete nosso para não esquecer 😉", $this->app->db, "user");

                $sql3 = "UPDATE scheduling set scheduling.reminder_count = 1 where scheduling.scheduling_id = :schedulingId";
                $sth3 = $this->app->db->prepare($sql3);
                $sth3->bindValue(':schedulingId',$scheduling['scheduling_id']);
                $sth3->execute();           
            }
        }
        $this->app->db->commit();
    }catch(\Throwable $e){
        return $response->withJson(false);
        $this->app->db->rollBack();
    }
}

//CRON
function reminderSchedulingHour(){
    $this->app->db->beginTransaction();
    try{
        $sql = "SELECT scheduling.scheduling_id, scheduling.user_id, scheduling.company_name, scheduling.user_name FROM scheduling
        WHERE TIMESTAMPDIFF(HOUR, :hoje, scheduling.start_time) <= 1 AND scheduling.status = :status and scheduling.reminder_count = :reminderStatus and scheduling.source = :nuppin";

        $sth = $this->app->db->prepare($sql);
        $sth->bindValue(":hoje",date('Y-m-d H:i:s'));
        $sth->bindValue(":status",'accepted');
        $sth->bindValue(":reminderStatus",1);
        $sth->bindValue(":nuppin","nuppin");
        $sth->execute();
        $data = $sth->fetchAll();

        if(sizeof($data)>0){
            foreach ($data as $scheduling) {
                //todo notification
                $this->send_notification_reminder_scheduling($scheduling['user_id'], "Em menos de uma hora seu agendamento na ".$scheduling['company_name'], "Tá pertinho, ajudamos a não se esquecer 😉", $this->app->db, "user");

                $sql3 = "UPDATE scheduling set scheduling.reminder_count = 2 where scheduling.scheduling_id = :schedulingId";
                $sth3 = $this->app->db->prepare($sql3);
                $sth3->bindValue(':schedulingId',$scheduling['scheduling_id']);
                $sth3->execute();           
            }
        }
        $this->app->db->commit();
    }catch(\Throwable $e){
        $this->app->db->rollBack();
    }
}














function discord_notification($array,$link){
    $headers = array
            (
                'Content-Type: application/json'
            );
    $ch = curl_init();
    curl_setopt( $ch,CURLOPT_URL, $link);
    curl_setopt( $ch,CURLOPT_POST, true );
    curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode($array));
    $result = curl_exec($ch );
    curl_close( $ch );
    return $result;
}

function joe($message){
     $arrayJson = array(
        "content" => $message
        );

     $this->discord_notification($arrayJson,"https://discord.com/api/webhooks/742246533168234506/-VvqqzxOPZLYgt6CNHxZphNE3uS6ih62ONt52eFrv7g_ohUeDMuIOXUlFpKnGS9WueEk"); 
}

function leadBoy($message){
     $arrayJson = array(
        "content" => $message
        );

     $this->discord_notification($arrayJson,"https://discord.com/api/webhooks/742606959362179153/vHEBN_xMjKX0QR23jt6QTBDaqEEhJAVaClocx757u_1gN1QSmfg9e0_F8LfIRKa5CgG1");    
}

function voxPopuli($message){
     $arrayJson = array(
        "content" => $message
        );

     $this->discord_notification($arrayJson,"https://discord.com/api/webhooks/742611643816738867/x7bWAqloUGbFHk7dYZ080bpBkm9TCbmO5WhU06Aw0IoiIb-ExIY5EhCdY-STbgUeljvu");
 }


function mrGoodNews($message){
     $arrayJson = array(
        "content" => $message
        );

     $this->discord_notification($arrayJson,"https://discord.com/api/webhooks/742177949813309441/ESH0dZqhBCIrP2N-Sm-3eY3tmM7IlRhe_ZacqILBBhpxeM8C4AikE-ZY5X5tQRHA2Lwh");  
}

function houston($message){
     $arrayJson = array(
        "content" => $message
        );

     $this->discord_notification($arrayJson,"https://discord.com/api/webhooks/742178455063363657/UhVcXMqseHQm8golv-k7FmnUmE2GpsChCKlGmeQfQGfp74ueRZxwaRHtRRnOBb2I0_vC");
}

function instagrammer($message){
     $arrayJson = array(
        "content" => $message
        );

     $this->discord_notification($arrayJson,"https://discord.com/api/webhooks/742547643913207878/10TmeXwy4FLGXvsi0rsBY9Q9fONbCxpV0zV13B0M5DQJtn_D8984TMO3O8dHL3obuuNQ");  
}

function nerd(){

try{
    $sth = $this->app->db->prepare("
        SELECT COALESCE(SUM(view_count.count),0) as view_count FROM view_count where DATE(view_count.created_date) = date(CURDATE() - INTERVAL 1 DAY);
        SELECT COUNT(orders.order_id) AS orders_count FROM orders where DATE(orders.created_date) = date(CURDATE() - INTERVAL 1 DAY);
        SELECT COUNT(scheduling.scheduling_id) AS scheduling_count FROM scheduling where DATE(scheduling.created_date) = date(CURDATE() - INTERVAL 1 DAY);
        SELECT COUNT(product.product_id) AS product_count FROM product where DATE(product.created_date) = date(CURDATE() - INTERVAL 1 DAY);
        SELECT COUNT(invoice.invoice_id) AS invoice_count FROM invoice where DATE(invoice.created_date) = date(CURDATE() - INTERVAL 1 DAY);

        SELECT COUNT(invoice.invoice_id) AS invoice_paid_count,
        COALESCE(SUM(invoice.total_amount),0) AS total_amount,
        COALESCE(SUM(invoice.subtotal_amount),0) AS subtotal_amount, 
        COALESCE(SUM(invoice.fee_amount),0) AS fee_amount FROM invoice where DATE(invoice.completed_date) = CURDATE() AND invoice.status = 'paid';

        SELECT COUNT(invoice.invoice_id) AS invoice_canceled_count,
        COALESCE(SUM(invoice.total_amount),0) AS total_amount,
        COALESCE(SUM(invoice.subtotal_amount),0) AS subtotal_amount, 
        COALESCE(SUM(invoice.fee_amount),0) AS fee_amount FROM invoice where DATE(invoice.completed_date) = CURDATE() AND invoice.status = 'canceled';

        SELECT COUNT(company.company_id) AS company_count FROM company where DATE(company.created_date) = date(CURDATE() - INTERVAL 1 DAY);
        SELECT COUNT(coupon.coupon_id) AS coupon_count FROM coupon where DATE(coupon.created_date) = date(CURDATE() - INTERVAL 1 DAY);
        SELECT COUNT(employee.employee_id) AS employee_count FROM employee where DATE(employee.hired_date) = date(CURDATE() - INTERVAL 1 DAY);
        SELECT COUNT(users.user_id) AS users_count FROM users where DATE(users.created_date) = date(CURDATE() - INTERVAL 1 DAY);");
    $sth->execute();
    $view = $sth->fetchObject();
    $sth->nextRowset();
    $order = $sth->fetchObject();
    $sth->nextRowset();
    $scheduling = $sth->fetchObject();
    $sth->nextRowset();
    $product = $sth->fetchObject();
    $sth->nextRowset();
    $invoice = $sth->fetchObject();
    $sth->nextRowset();
    $invoiceP = $sth->fetchObject();
    $sth->nextRowset();
    $invoiceC = $sth->fetchObject();
    $sth->nextRowset();
    $company = $sth->fetchObject();
    $sth->nextRowset();
    $coupon = $sth->fetchObject();
    $sth->nextRowset();
    $employee = $sth->fetchObject();
    $sth->nextRowset();
    $users = $sth->fetchObject();

    $report = new \stdClass();
    $report->view = $view;
    $report->order = $order;
    $report->scheduling = $scheduling;
    $report->product = $product;
    $report->invoice = $invoice;
    $report->invoiceP = $invoiceP;
    $report->invoiceC = $invoiceC;
    $report->company = $company;
    $report->coupon = $coupon;
    $report->employee = $employee;
    $report->users = $users;

    $stringMessage = "======= Relatório do dia ======="
    ."\nViews: ".$report->view->view_count
    ."\nPedidos: ".$report->order->orders_count
    ."\nAgendamentos: ".$report->scheduling->scheduling_count
    ."\nProdutos cadastrados: ".$report->product->product_count
    ."\nFaturas geradas: ".$report->invoice->invoice_count
    ."\nBoletos pagos: ".$report->invoiceP->invoice_paid_count
    ."\nBoletos cancelados: ".$report->invoiceC->invoice_canceled_count
    ."\nFaturado: R$ ".$report->invoiceP->total_amount." (sub: ".$report->invoiceP->subtotal_amount." - fee: ".$report->invoiceP->fee_amount.")"
    ."\nNão pago: R$ ".$report->invoiceC->total_amount." (sub: ".$report->invoiceC->subtotal_amount." - fee: ".$report->invoiceC->fee_amount.")"
    ."\nEmpresas novas: ".$report->company->company_count
    ."\nCupons criados: ".$report->coupon->coupon_count
    ."\nMovimentação de equipe: ".$report->employee->employee_count
    ."\nUsuários novos: ".$report->users->users_count
    ;

    
    $arrayJson = array(
    "content" => $stringMessage
    );

     $this->discord_notification($arrayJson,"https://discord.com/api/webhooks/738476914699665530/XNWQfTlwKLBOc_-KCaqvjIUs2Ie7LDu_zvbC3qsyLWJRWASPViLL2QqP3yFLbozTRvAF");

    }catch(\Throwable $e){
        return false;
    }
 }

function viewCounter($companyId){
        $sth1 = $this->app->db->prepare("SELECT * FROM view_count where view_count.company_id = :companyId and view_count.created_date = :today");
        $sth1->bindValue(":companyId", $companyId);
        $sth1->bindValue(":today", date('Y-m-d'));
        $sth1->execute();
        $view = $sth1->fetchObject();

        if($view){
            $sth2 = $this->app->db->prepare("UPDATE view_count SET view_count.count = view_count.count + 1 WHERE view_count.company_id = :companyId and view_count.created_date = curdate()");
            $sth2->bindValue(':companyId',$companyId);
            $sth2->execute();
        }else{
            $sth2 = $this->app->db->prepare("INSERT into view_count(view_count_id, company_id, count, created_date) values(:id,:companyId,:count,:created_date)");
            $sth2->bindValue(':id',$this->uniqidReal(9));
            $sth2->bindValue(':companyId',$companyId);
            $sth2->bindValue(':count',1);
            $sth2->bindValue(':created_date',date('Y-m-d'));
            $sth2->execute();
        }
}


function notificationRegister($tokenNotification, $userId, $source){
    $this->app->db->beginTransaction();
        try{
    
        $sth = $this->app->db->prepare("DELETE from notification where notification_id = :notificationId");
        $sth->bindValue(":notificationId",$tokenNotification);
        $sth->execute(); 

        $sth1 = $this->app->db->prepare("SELECT * FROM token where token.user_id = :userId and not exists (select * from notification where notification.refresh_token = token.token_id) order by token.created_date desc limit 1");
        $sth1->bindValue(":userId", $userId);
        $sth1->execute();
        $refreshToken = $sth1->fetchObject();

        $sth2 = $this->app->db->prepare("INSERT INTO notification (notification_id, user_id, source, refresh_token) values(:notificationId, :userId, :source, :refreshToken)");
        $sth2->bindValue(":userId",$userId);
        $sth2->bindValue(":notificationId",$tokenNotification);
        $sth2->bindValue(":source",$source);
        $sth2->bindValue(":refreshToken",$refreshToken->token_id);
        $sth2->execute();   

        $sth4 = $this->app->db->prepare("DELETE from temp_notification where temp_notification_id = :notificationId");
        $sth4->bindValue(":notificationId",$tokenNotification);
        $sth4->execute(); 

        $this->app->db->commit();

        $this->mrGoodNews("Token de notificação registrado (".$source.") 🔔");
    }catch(\Throwable $e){
        $this->app->db->rollBack();
        throw new \Exception(false);
    }
}

function notificationTempToken($tokenNotification, $source){
    $sth1 = $this->app->db->prepare("SELECT * FROM temp_notification where temp_notification.temp_notification_id = :notificationId");
    $sth1->bindValue(":notificationId", $tokenNotification);
    $sth1->execute();
    $notification = $sth1->fetchObject();

    if(!$notification){
        $sth = $this->app->db->prepare("INSERT into temp_notification (temp_notification_id, source) values(:notificationId, :source)");
        $sth->bindValue(":notificationId",$tokenNotification);
        $sth->bindValue(":source",$source);
        $sth->execute(); 

        $this->mrGoodNews("Token temporario de notificação (".$source.") 🔔");
    }
}


function getListAnalises($request,$response,$args){
     $token = $request->getAttribute("token");

    if (!($args['companyId'] == $token["comp"] || $token["admin"] == true)) {
        return $response->withJson(false)->withStatus(403);
    }
    $sth = $this->app->db->prepare("
        SELECT * FROM validation WHERE validation.company_id = :companyId;
        SELECT vm.* FROM validation_message as vm 
        join validation on validation.company_id = :companyId
        where vm.validation_id = validation.validation_id");
    $sth->bindParam("companyId", $args['companyId']);
    $sth->execute();
    $validation = $sth->fetchObject();
    $sth->nextRowset();
    $validation->messages = $sth->fetchAll();
    return $response->withJson($validation);
}


function getCompaniesValidation($request, $response, $args){
    $token = $request->getAttribute("token");

    if (!($token["admin"] == true)) {
        return $response->withJson(false)->withStatus(403);
    }

    $sth = $this->app->db->prepare("
        SELECT company.*, validation.validation_id,
        cc.name as category_name,
        sc.name as subcategory_name
        FROM company
        join category_company as cc on cc.category_company_id = company.category_company_id
        join subcategory_company as sc on sc.subcategory_company_id = company.subcategory_company_id
        join validation on validation.company_id = company.company_id and validation.status = 'pending'
    ");
    $sth->execute();
    $company = $sth->fetchAll();

    $arrayJson = array(
        "company" => $company,
    );

    return $response->withJson($arrayJson);
}

function addValidationMessage($request, $response){
    $validationMessage = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($token["admin"] == true)) {
        return $response->withJson(false)->withStatus(403);
    }

    $keys = array_keys($validationMessage); 

    $this->app->db->beginTransaction();
    try{
        $sql = "INSERT INTO validation_message (".implode(',', $keys).") VALUES (:".implode(",:", $keys).")";
        $sth = $this->app->db->prepare($sql);
        foreach ($validationMessage as $key => $value) {
            if($key == "validation_message_id"){
                $sth ->bindValue(":".$key, $this->uniqidReal(9));
            }else{
                $sth ->bindValue(':'.$key,$value);
            }
        }
        $sth->execute();
        $this->app->db->commit();
        return $response->withJson(true);
    }catch(\Throwable $e){
        $this->app->db->rollBack();
        return $response->withJson(false)->withStatus(500);
    }
}

function updateValidationCompany($request, $response){
    $validation = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($token["admin"] == true)) {
        return $response->withJson(false)->withStatus(403);
    }

    $this->app->db->beginTransaction();
    try{
        $sth1 = $this->app->db->prepare("UPDATE validation set validation.status =  :status WHERE validation.validation_id = :validationId");
        $sth1->bindValue(':status',$validation['status']);
        $sth1->bindValue(':validationId',$validation['validation_id']);
        $sth1->execute();

        if ($validation['status'] == "accepted") {
            $sth2 = $this->app->db->prepare("UPDATE company set company.visibility = 1 WHERE company.company_id = :companyId");
            $sth2->bindValue(':companyId',$validation['company_id']);
            $sth2->execute();
            
            $this->send_notification_no_tag($validation['company_id'], "Já está liberado para ficar online", "Estabelecimento aprovado  ✔", $this->app->db, "company");
        }else{
             $this->send_notification_no_tag($validation['company_id'], "Veja os detalhes do que precisa ser feito", "Estabelecimento não liberado ❌", $this->app->db, "company");
        }

        $this->app->db->commit();
        return $response->withJson(true);
    }catch(\Throwable $e){
        $this->app->db->rollBack();
        return $response->withJson(false)->withStatus(500);
    }
}

function deleteValidationMessage($request, $response){
    $validationMessage = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($token["admin"] == true)) {
        return $response->withJson(false)->withStatus(403);
    }

    $this->app->db->beginTransaction();
    try{
        $sth1 = $this->app->db->prepare("DELETE from validation_message AS vm WHERE vm.validation_message_id = :validationMessageId");
        $sth1->bindValue(':validationMessageId',$validationMessage['validation_message_id']);
        $sth1->execute();
        $this->app->db->commit();
        return $response->withJson(true);
    }catch(\Throwable $e){
        $this->app->db->rollBack();
        return $response->withJson(false)->withStatus(500);
    }
}

function logicaPrecoDelivery($cartCompany, $type){
    $cartCompany->delivery_amount = 0;
    if($type == "delivery"){
        if (($cartCompany->max_radius_free == 0 || $cartCompany->max_radius_free < $cartCompany->distance)) {
            switch ($cartCompany->delivery_type_value) {
                case 1:
                    $cartCompany->delivery_amount = $cartCompany->delivery_fixed_fee;
                    break;
                case 2:
                    $cartCompany->delivery_amount = $cartCompany->delivery_fixed_fee + ($cartCompany->delivery_variable_fee * $cartCompany->distance);
                    break;
                case 3:
                    $cartCompany->delivery_amount = $cartCompany->delivery_variable_fee * $cartCompany->distance;
                    break;
                case 4:
                    if ($cartCompany->min_purchase > ($cartCompany->subtotal_amount - $cartCompany->discount_amount)) {
                        $cartCompany->not_enough_amount_for_free_delivery = 1;
                    } else {
                        $cartCompany->delivery_amount = 0;
                    }
                    break;
            }
        } else {
            if ($cartCompany->min_purchase > ($cartCompany->subtotal_amount - $cartCompany->discount_amount)) {
                if ($cartCompany->delivery_type_value != 4) {
                    switch ($cartCompany->delivery_type_value) {
                        case 1:
                            $cartCompany->delivery_amount = $cartCompany->delivery_fixed_fee;
                            break;
                        case 2:
                            $cartCompany->delivery_amount = $cartCompany->delivery_fixed_fee + ($cartCompany->delivery_variable_fee * $cartCompany->distance);
                            break;
                        case 3:
                            $cartCompany->delivery_amount = $cartCompany->delivery_variable_fee * $cartCompany->distance;
                            break;
                    }
                    $cartCompany->free_delivery_over_available = 1;
                }
            } else {
                $cartCompany->delivery_amount = 0;
            }
        }
    }

    $cartCompany->delivery_amount = $this->roudHalf($cartCompany->delivery_amount);
    $cartCompany->total_amount = ($cartCompany->subtotal_amount - $cartCompany->discount_amount) + $cartCompany->delivery_amount;
}

function couponCartInfo($cartCompany, $couponId){
    $sth = $this->app->db->prepare("
        SELECT coupon.*
        from coupon
        join coupon_users on coupon_users.coupon_id = coupon.coupon_id
        where coupon_users.user_id = :userId and coupon.coupon_id = :couponId and coupon.company_id = :companyId and coupon_users.order_id is null and coupon.due_date > :data");
    $sth->bindValue(":userId", $cartCompany->user_id);
    $sth->bindValue(":companyId", $cartCompany->company_id);
    $sth->bindValue(":couponId", $couponId);
    $sth->bindValue(":data", date("Y-m-d H:i:s"));  
    $sth->execute();
    $coupon = $sth->fetchObject();
  
    if ($coupon) {
        if ($coupon->min_purchase > $cartCompany->subtotal_amount) {
            $sth = $this->app->db->prepare("UPDATE cart_info as info set info.coupon_id = null where info.user_id = :userId and info.company_id = :companyId");
            $sth->bindValue(":userId", $cartCompany->user_id);
            $sth->bindValue(":companyId", $cartCompany->company_id);
            $sth->execute();
            return;
          }
        if ($coupon->discount_type = 1) {
            $cartCompany->discount_amount = $coupon->value > $cartCompany->subtotal_amount ? $cartCompany->subtotal_amount : $coupon->value;
        } else {
            $cartCompany->discount_amount =  $this->roudHalf(($coupon->value / 100) * $cartCompany->subtotal_amount);
        }
    }
}

function roudHalf($num){
    return round($num * 10) / 10.0;
}

function updateCartInfo($request, $response) {
    $inputCartInfo = $request->getParsedBody();

    try{
        $sth = $this->app->db->prepare("UPDATE cart_info as info SET info.payment_method = :paymentMethod, info.type = :type, info.coupon_id = :couponId where info.user_id = :userId and info.company_id = :companyId");
        $sth->bindValue(':paymentMethod',$inputCartInfo['payment_method']);
        $sth->bindValue(':couponId',$inputCartInfo['coupon_id']);
        $sth->bindValue(':type',$inputCartInfo['type']);
        $sth->bindValue(':userId',$inputCartInfo['user_id']);
        $sth->bindValue(':companyId',$inputCartInfo['company_id']);
        $sth->execute();
        return $response->withJson(true);
    }catch(\Throwable $e){
        return $response->withJson(false);
    }
}

function updateCartCouponInfo($request, $response) {
    $inputCartInfo = $request->getParsedBody();

    $sth = $this->app->db->prepare("
        SELECT
        (sum((product.price + (SELECT COALESCE(sum(size.price),0) FROM size where size.size_id = cart.size_id) + (SELECT COALESCE(SUM(cart_extra.quantity * extra.price),0) FROM cart_extra join extra on cart_extra.extra_id = extra.extra_id where cart_extra.cart_id = cart.cart_id))* cart.quantity)) subtotal_amount
        FROM cart
        join product on cart.product_id = product.product_id
        where cart.user_id = :userId and cart.source = 'nuppin';

        SELECT coupon.*
        from coupon
        join coupon_users on coupon_users.coupon_id = coupon.coupon_id
        where coupon_users.user_id = :userId and
        coupon.coupon_id = :couponId and
        coupon.company_id = :companyId and 
        coupon_users.order_id is null and 
        coupon.due_date > :data");
    $sth->bindValue(":userId", $inputCartInfo["user_id"]);
    $sth->bindValue(":companyId", $inputCartInfo["company_id"]);
    $sth->bindValue(":couponId", $inputCartInfo["coupon_id"]);
    $sth->bindValue(":data", date("Y-m-d H:i:s"));  
    $sth->execute();
    $checkerCart = $sth->fetchObject();
    $sth->nextRowset();
    $checkerCoupon = $sth->fetchObject();
  
    if ($checkerCoupon) {
        if ($checkerCoupon->min_purchase < $checkerCart->subtotal_amount) {
            try{
                $sth1 = $this->app->db->prepare("UPDATE cart_info as info set info.coupon_id = :couponId where info.user_id = :userId and info.company_id = :companyId");
                $sth1->bindValue(":userId", $inputCartInfo["user_id"]);
                $sth1->bindValue(":companyId", $inputCartInfo["company_id"]);
                $sth1->bindValue(":couponId", $inputCartInfo["coupon_id"]);
                $sth1->execute();
                return $response->withJson(true);
            }catch(\Throwable $e){
                return $response->withJson(false);
            }
          }else{
            $error = array();
            $error['error_code'] = "001";
            $error['error_message'] = "Pedido minimo não atingido para esse cupom!";
            $arrayJson = array("error" => $error);
            return $response->withJson($arrayJson);
          }
    }else{
        $error = array();
        $error['error_code'] = "002";
        $error['error_message'] = "Cupom invalido!";
        $arrayJson = array("error" => $error);
        return $response->withJson($arrayJson);
    }
}

function verificarPedido($safeOrder){
    foreach($safeOrder->product as $valueItem){
        $sthStock = $this->app->db->prepare("SELECT * FROM product WHERE product.product_id = :item");
        $sthStock ->bindValue(':item',$valueItem['product_id']);
        $sthStock->execute();
        $product = $sthStock->fetchObject();

        if ($product->is_stock == 1) {
            $sthStock = $this->app->db->prepare("SELECT product.product_id FROM product WHERE product.is_stock = 1 and product.stock_quantity >= (select sum(cart.quantity) from cart where cart.product_id = :item and cart.user_id = :userId) and product.product_id = :item");
            $sthStock ->bindValue(':item',$valueItem['product_id']);
            $sthStock ->bindValue(':userId',$safeOrder->user_id);
            $sthStock->execute();
            $stock = $sthStock->fetchObject();      
        }else if ($product->is_multi_stock == 1){
            $sthStock = $this->app->db->prepare("SELECT size.product_id from size where size.size_id = :sizeId AND size.product_id = :productId AND size.stock_quantity >= (select sum(cart.quantity) from cart where cart.product_id = :productId and cart.user_id = :userId and cart.size_id = :sizeId)");
            $sthStock ->bindValue(':productId',$valueItem['product_id']);
            $sthStock ->bindValue(':sizeId',$valueItem['size_id']);
            $sthStock ->bindValue(':userId',$safeOrder->user_id);
            $sthStock->execute();
            $stock = $sthStock->fetchObject();
        }

        if (!$stock && ($product->is_stock == 1 || $product->is_multi_stock == 1)) {
            $error = array();
            $error['error_code'] = "001";
            $error['error_message'] = "Tem um ou mais produtos com estoque insuficiente";
            $arrayJson = array("error" => $error,"order" => false);
            return $arrayJson;
        }
    }

    if (!($safeOrder->source == "nuppin_company")) {
        if($safeOrder->coupon_id){
            $sqlCoupon = "SELECT coupon.min_purchase FROM coupon WHERE coupon.coupon_id = :couponId;";
            $sthCoupon = $this->app->db->prepare($sqlCoupon);
            $sthCoupon->bindValue(':couponId',$safeOrder->coupon_id);
            $sthCoupon->execute();
            $coupon = $sthCoupon->fetchObject();

            if ($coupon->min_purchase > $safeOrder->subtotal_amount) {
                $error = array();
                $error['error_code'] = "002";
                $error['error_message'] = "Pedido abaixo do minimo para o cupom cadastrado";
                $arrayJson = array("error" => $error,"order" => false);
                return $arrayJson;
            }
        }
    }
}

function adicionarProdutosDoPedido($safeOrder){
    foreach($safeOrder as $key => $value){
        if($key != "product"){
            $sets[] = $key;
        }
    }   

    $sth = $this->app->db->prepare("INSERT into orders(".implode(',', $sets).") values (:".implode(",:", $sets).")");
    foreach($safeOrder as $orderKey => $orderValue){
        if($orderKey != "product"){
            if($orderKey == "order_id"){
                $safeOrder->order_id = $this->uniqidReal(6);
                $sth ->bindValue(":".$orderKey,$safeOrder->order_id);
            }else{
                $sth ->bindValue(":".$orderKey,$orderValue);
            }
        }
    }
    $sth->execute();

    $arrayExtras  = array();

    foreach($safeOrder->product as $itemKey => $itemValues){
        $extras = [];
        $itemId;

        foreach($itemValues as $itemColumnKey => $itemColumnValue){
            if($itemColumnKey != "extra"){
                $setsP[] = $itemColumnKey;
            }else{
                $extras = $itemColumnValue;
            }
        }

        $sth2 = $this->app->db->prepare("INSERT into order_item(".implode(',', $setsP).") values (:".implode(",:", $setsP).");");
        foreach($itemValues as $itemKey => $itemValue){
            if($itemKey != "extra"){
                if ($itemKey == "order_id") {
                    $sth2->bindValue(":".$itemKey,$safeOrder->order_id);
                }else if ($itemKey == "order_item_id") {
                    $safeOrder->order_item_id = $this->uniqidReal(9);
                    $sth2->bindValue(":".$itemKey,$safeOrder->order_item_id);
                }else{
                    $sth2->bindValue(":".$itemKey,$itemValue);
                }
            }
        }
        $sth2->execute();
        $sth2->closeCursor();
        $setsP = [];

        foreach($extras as $extrasKey => $arrayExtras){
            foreach($arrayExtras as $extrasColumnkey => $extrasColumnValue){
                $keyE[] = $extrasColumnkey;
            }
          
            $sth4 = $this->app->db->prepare("INSERT into order_item_extra(".implode(',', $keyE).") values (:".implode(",:", $keyE).");");

            foreach($arrayExtras as $ekey => $evalue){
                if ($ekey == "order_id") {
                    $sth4->bindValue(":".$ekey,$safeOrder->order_id);
                }else if ($ekey == "order_item_id") {
                    $sth4->bindValue(":".$ekey,$safeOrder->order_item_id);
                }else if ($ekey == "order_item_extra_id") {
                    $safeOrder->order_item_extra_id = $this->uniqidReal(9);
                    $sth4->bindValue(":".$ekey,$safeOrder->order_item_extra_id);
                }else{
                    $sth4->bindValue(":".$ekey,$evalue);
                }
            }

            $sth4->execute();
            $sth4->closeCursor();
            $keyE = [];
        }
    }

    $sth3 = $this->app->db->prepare("DELETE from cart where cart.user_id = :id and cart.source = :source");
    $sth3 ->bindValue(":id",$safeOrder->user_id);
    $sth3 ->bindValue(":source",$safeOrder->source);
    $sth3->execute();

    $sth4 = $this->app->db->prepare("DELETE from cart_info where cart_info.user_id = :id and cart_info.source = 'nuppin'");
    $sth4 ->bindValue(":id",$safeOrder->user_id);
    $sth4->execute();
}

function atualizarCupom($safeOrder){
    if ($safeOrder->coupon_id) {   
        $sql3 ="UPDATE coupon_users set coupon_users.order_id = :orderId where coupon_users.coupon_id = :couponId and coupon_users.user_id = :userId";
        $sth3 = $this->app->db->prepare($sql3);
        $sth3->bindValue(":orderId",$orderId);
        $sth3->bindValue(":couponId",$safeOrder->coupon_id);
        $sth3->bindValue(":userId",$safeOrder->user_id);
        $sth3->execute();
    }          
}

function inserirNoCarrinho($inputCart){
    if ($inputCart["source"] == "nuppin") {
        $sth = $this->app->db->prepare("SELECT * FROM cart_info as info where info.user_id = :userId and info.company_id = :companyId");
        $sth->bindValue(":userId", $inputCart['user_id']);
        $sth->bindValue(":companyId", $inputCart['company_id']);
        $sth->execute();
        $info = $sth->fetchObject();

        if(!$info){
            $sth = $this->app->db->prepare("DELETE from cart_info as info where info.user_id = :userId");
            $sth->bindValue(":userId", $inputCart['user_id']);
            $sth->execute();

            $sth1 = $this->app->db->prepare("
                SELECT company.is_delivery, company.is_local FROM company where company.company_id = :companyId;
                SELECT users.full_name  from users where users.user_id = :userId");
            $sth1->bindValue(":companyId", $inputCart['company_id']);
            $sth1->bindValue(":userId", $inputCart['user_id']);
            $sth1->execute();
            $company = $sth1->fetchObject();
            $sth1->nextRowset();
            $user = $sth1->fetchObject();


            $sth2 = $this->app->db->prepare("INSERT INTO cart_info(user_id, user_name, company_id, type, source) values(:userId, :userName, :companyId, :type, :source)");
            $sth2->bindValue(":userId", $inputCart['user_id']);
            $sth2->bindValue(":userName", $user->full_name);
            $sth2->bindValue(":companyId", $inputCart['company_id']);
            if ($company->is_delivery == 1) {
                 $sth2->bindValue(":type", "delivery");
            }else {
                 $sth2->bindValue(":type", "local");
            }
            $sth2->bindValue(":source", $inputCart['source']);
            $sth2->execute();
        }

    }

        $sth8 = $this->app->db->prepare("DELETE from cart where cart.user_id = :userId and cart.company_id != :companyId and cart.source = :source;");
        $sth8->bindValue(":userId",$inputCart['user_id']);
        $sth8->bindValue(":companyId",$inputCart['company_id']);
        $sth8->bindValue(":source",$inputCart['source']);
        $sth8->execute();

        foreach($inputCart as $key => $value){
            if($key != "extra"){
                $sets[] = $key;
            }else{
                $extras = $value;
            }
        }

        $sth2 = $this->app->db->prepare("INSERT into cart(".implode(',', $sets).") values (:".implode(",:", $sets).")");
        foreach ($inputCart as $key => $value) {
            if ($key != "extra") {
                if($key == "cart_id"){
                    $inputCart["cart_id"] = $this->uniqidReal(9);
                    $sth2->bindValue(":".$key, $inputCart["cart_id"]);
                }else{
                    $sth2->bindValue(':'.$key,$value);
                }
            }
        }
        $sth2->execute();
        $sets = [];
        
        foreach($extras as $extrasKey => $arrayExtras){
            foreach($arrayExtras as $key => $value){
                $keys[] = $key;
            }
              
            $sth3 = $this->app->db->prepare("INSERT into cart_extra(".implode(',', $keys).") values (:".implode(",:", $keys).")");
            foreach ($arrayExtras as $key1 => $value1) {
                if($key1 == "cart_id"){
                    $sth3->bindValue(":".$key1, $inputCart["cart_id"]);
                }else if($key1 == "cart_extra_id"){
                    $sth3->bindValue(":".$key1, $this->uniqidReal(9));
                }else{
                    $sth3->bindValue(':'.$key1,$value1);
                }
            }
            $sth3->execute();
            $keys = [];
            $extras = [];
        }
}

function orderSafeComplementMethods($order){

    $sth = $this->app->db->prepare("SELECT * FROM company where company.company_id = :companyId");
    $sth->bindValue(":companyId", $order->company_id);
    $sth->execute();
    $company = $sth->fetchObject();
    $company->delivery_amount = 0;

    if ($order->type == "delivery") {
        if (($company->max_radius_free == 0 || $company->max_radius_free < $company->distance)) {
            switch ($company->delivery_type_value) {
                case 1:
                    $order->delivery_amount = $company->delivery_fixed_fee;
                    break;
                case 2:
                    $order->delivery_amount = $company->delivery_fixed_fee + ($company->delivery_variable_fee * $order->distance);
                    break;
                case 3:
                    $order->delivery_amount = $company->delivery_variable_fee * $order->distance;
                    break;
                case 4:
                    if ($company->min_purchase > ($order->subtotal_amount - $order->discount_amount)) {
                        if(!($order->source == "nuppin_company")){
                            throw new \Exception('Pedido abaixo do minimo para entrega grátis');
                        }
                    } else {
                        $order->delivery_amount = 0;
                    }
                    break;
            }
        } else {
            if ($company->min_purchase > ($order->subtotal_amount - $order->discount_amount)) {
                if ($company->delivery_type_value != 4) {
                    switch ($company->delivery_type_value) {
                        case 1:
                            $order->delivery_amount = $company->delivery_fixed_fee;
                            break;
                        case 2:
                            $order->delivery_amount = $company->delivery_fixed_fee + ($company->delivery_variable_fee * $order->distance);
                            break;
                        case 3:
                            $order->delivery_amount = $company->delivery_variable_fee * $order->distance;
                            break;
                    }
                }
            } else {
                $company->delivery_amount = 0;
            }
        }
    }

    $order->delivery_amount = $this->roudHalf($order->delivery_amount);
    $order->total_amount = ($order->subtotal_amount - $order->discount_amount) + $order->delivery_amount;
}









































// COMPRA E CARRINHO PELO APP DO PARCEIRO =====================================================================

function addCartCompany($request,$response){
    $inputCart = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputOrder["company_id"] == $token["comp"] || $args['companyId'] == $token["emp_comp"])) {
        return $response->withJson(false)->withStatus(403);
    }
    
    $this->app->db->beginTransaction();
    try{

        $inputCart["source"] = "nuppin_company";

        $this->inserirNoCarrinho($inputCart);
        
        $this->app->db->commit();        

        $this->mrGoodNews("Produto adicionado ao caixa 📇");

        return $response->withJson($inputCart);

    }catch(\Throwable $e){
        $this->app->db->rollBack();
        return $response->withJson(false);
    } 
}

function getCartCompany($request,$response,$args){
    $token = $request->getAttribute("token");

    if (!($args['company'] == $token["comp"] || $args['company'] == $token["emp_comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    //colocar a função de fuso horario aqui
    date_default_timezone_set($this->get_nearest_timezone($args['latitude'],$args['longitude'],$args['countryCode']));

    $sth = $this->app->db->prepare(
        "SELECT
        cart.user_id,
        company.name,
        company.company_id,
        company.category_company_id,
        company.subcategory_company_id,
        company.min_purchase,
        company.delivery_fixed_fee,
        company.delivery_type_value,
        company.delivery_min_time,
        company.delivery_max_time,
        company.delivery_variable_fee,
        company.max_radius_free,
        company.max_radius,
        company.model_type,
        company.is_delivery,
        company.is_local,
        company.is_pos,
        company.full_address,
        company.latitude,
        company.longitude,
        (sum((product.price + (SELECT COALESCE(sum(size.price),0) FROM size where size.size_id = cart.size_id) + (SELECT COALESCE(SUM(cart_extra.quantity * extra.price),0) FROM cart_extra join extra on cart_extra.extra_id = extra.extra_id where cart_extra.cart_id = cart.cart_id))* cart.quantity)) subtotal_amount,

       (case when company.model_type = 'fixed' then (ST_Distance_Sphere(POINT(company.longitude, company.latitude), POINT(:longitude,:latitude))/1000) else ((case when IF(EXISTS(SELECT mobile.company_id FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL),1,0) = 1 then (SELECT ST_Distance_Sphere(POINT(mobile.longitude, mobile.latitude), POINT(:longitude,:latitude))/1000 FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL) else (ST_Distance_Sphere(POINT(company.longitude, company.latitude), POINT(:longitude,:latitude))/1000)end))end) as distance,


        IF((case when company.model_type = 'fixed' then (ST_Distance_Sphere(POINT(company.longitude, company.latitude), POINT(:longitude,:latitude))/1000) else ((case when IF(EXISTS(SELECT mobile.company_id FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL),1,0) = 1 then (SELECT ST_Distance_Sphere(POINT(mobile.longitude, mobile.latitude), POINT(:longitude,:latitude))/1000 FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL) else (ST_Distance_Sphere(POINT(company.longitude, company.latitude), POINT(:longitude,:latitude))/1000)end))end) < company.max_radius and company.visibility = 1 ,1,0) as is_available

        from company
        left join cart on cart.company_id = company.company_id AND cart.user_id = :userId and cart.source = 'nuppin_company'
        left join product on cart.product_id = product.product_id
        where company.company_id = :companyId
        group by company.company_id;

        SELECT 
        cart.company_id,
        product.product_id,
        cart.cart_id,
        cart.user_id,
        cart.size_id,
        cart.size_name,
        product.name,
        cart.source,
        ((product.price + COALESCE(SUM(distinct size.price),0) + COALESCE(SUM(distinct cart_extra.quantity * extra.price),0))* cart.quantity) as total_price,
        cart.quantity,
        cart.note,
        (case when product.is_multi_stock = 1 then  (case when size.stock_quantity >= sum(distinct cart.quantity) then 1 ELSE 0 end) ELSE (case when sum(distinct cart.quantity) > product.stock_quantity AND product.is_stock = 1 then 0 ELSE 1 end) end) as is_available
        from cart
        join product on cart.product_id = product.product_id
        left join cart_extra on cart.cart_id = cart_extra.cart_id
        left join extra on cart_extra.extra_id = extra.extra_id
        left join size on cart.size_id = size.size_id
        where cart.user_id = :userId and cart.company_id = :companyId and cart.source = 'nuppin_company'
        GROUP BY cart.cart_id
        order by cart.created_date;

        SELECT * FROM cart_extra where cart_extra.user_id = :userId;
        "
    );
    $sth->bindParam(":userId", $args['userId']);       
    $sth->bindParam(":companyId", $args['companyId']);       
    $sth->bindParam(":latitude", $args['latitude']);       
    $sth->bindParam(":longitude", $args['longitude']);       
    $sth->execute();
    
    $company = $sth->fetchObject();
    $sth->nextRowset();
    $product = $sth->fetchAll();
    $sth->nextRowset();
    $extras = $sth->fetchAll();

    if($product != false){
        if (sizeof($extras)>0) {
            foreach ($product as &$p) {
                foreach ($extras as $e) {
                    if ($p["product_id"] == $e["product_id"] && $p["cart_id"] == $e["cart_id"]) {
                        $p["extra"][] = $e;
                    }
                }
            }
        }
        $company->product = $product;
        
    }else{
        $arrayJsonEmpty = array(
            "cart_company" => $company,
            "cart_company_empty" => true
        );
        return $response->withJson($arrayJsonEmpty);
    }

    $arrayJson = array(
        "cart_company" => $company,
        "cart_company_empty" => false
    );

    return $response->withJson($arrayJson);
}

function addOrderCompany($request,$response){
    $inputOrder = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputOrder["company_id"] == $token["comp"] || $args['companyId'] == $token["emp_comp"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $this->app->db->beginTransaction();

    try{

        $safeOrder = $this->safeWaytoPrepareOrderCompany($inputOrder);

        $arrayError = $this->verificarPedido($safeOrder);
        if ($arrayError) {
            return $response->withJson($arrayError);
        }

        $this->adicionarProdutosDoPedido($safeOrder);

        $this->decrementProductStock($safeOrder->product);

        $this->mrGoodNews("Pedido manual feito 🧾");

        $this->app->db->commit();

        $arrayJson = array("order" => $safeOrder);

        return $response->withJson($arrayJson);

    }catch(\Throwable $e){
        $this->app->db->rollBack();
        $error = array();
        $error['error_code'] = "001";
        $error['error_message'] = "Houve um erro ao concluir seu pedido";
        $arrayJson = array("error" => $error,"order" => false);
        return $response->withJson($arrayJson);
    }
}

function safeWaytoPrepareOrderCompany($inputOrder){
    $sth = $this->app->db->prepare(
        "SELECT
        cart.user_id as order_id,
        cart.user_id,
        company.company_id,
        company.name as company_name,
        (sum((product.price + (SELECT COALESCE(sum(size.price),0) FROM size where size.size_id = cart.size_id) + (SELECT COALESCE(SUM(cart_extra.quantity * extra.price),0) FROM cart_extra join extra on cart_extra.extra_id = extra.extra_id where cart_extra.cart_id = cart.cart_id))* cart.quantity)) subtotal_amount,

        (case when company.model_type = 'fixed' then (ST_Distance_Sphere(POINT(company.longitude, company.latitude), POINT(:userLongitude,:userLatitude))/1000) else ((case when IF(EXISTS(SELECT mobile.company_id FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL),1,0) = 1 then (SELECT ST_Distance_Sphere(POINT(mobile.longitude, mobile.latitude), POINT(:userLongitude,:userLatitude))/1000 FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL) else (ST_Distance_Sphere(POINT(company.longitude, company.latitude), POINT(:userLongitude,:userLatitude))/1000)end))end) as distance,
        cart.source
        from cart
        join product on cart.product_id = product.product_id
        join company on company.company_id = cart.company_id
        where cart.user_id = :id and cart.source = 'nuppin_company'
        group by cart.company_id;

        SELECT 
        cart.cart_id as order_item_id,
        cart.user_id as order_id,
        cart.product_id,
        cart.quantity,
        product.name,
        cart.size_name,
        cart.size_id,
        cart.note,
        (product.price + COALESCE(SUM(distinct size.price),0) + COALESCE(SUM(distinct cart_extra.quantity * extra.price),0)) as unit_amount,
        ((product.price + COALESCE(SUM(distinct size.price),0)+COALESCE(SUM(distinct cart_extra.quantity * extra.price),0))* cart.quantity) as total_amount
        
        from cart
        join product on cart.product_id = product.product_id
        left join cart_extra on cart.cart_id = cart_extra.cart_id
        left join extra on cart_extra.extra_id = extra.extra_id
        left join size on cart.size_id = size.size_id
        where cart.user_id = :id and cart.source = 'nuppin_company'
        GROUP BY cart.cart_id
        order by cart.created_date;

        SELECT 
        ce.extra_id as order_item_extra_id,
        ce.cart_id as order_item_id,
        ce.user_id as order_id, 
        ce.extra_id, 
        ce.collection_id, 
        ce.product_id,
        ce.name,
        ce.quantity,
        (SELECT extra.price FROM extra where extra.extra_id = ce.extra_id) as unit_amount,
        (SELECT sum(ce.quantity * extra.price) FROM extra where extra.extra_id = ce.extra_id) as total_amount
        FROM cart_extra as ce where ce.user_id = :id;
        "
    );
    $sth->bindParam(":id", $inputOrder["user_id"]);     
    $sth->bindParam(":userLatitude", $inputOrder["latitude"]);     
    $sth->bindParam(":userLongitude", $inputOrder["longitude"]);     
    $sth->bindValue(":selected", "1");                    
    $sth->execute();
    $company = $sth->fetchObject();
    $sth->nextRowset();
    $product = $sth->fetchAll();
    $sth->nextRowset();
    $extras = $sth->fetchAll();


    if($company != false){
        $company->note = $inputOrder["note"];
        $company->latitude = $inputOrder["latitude"];
        $company->longitude = $inputOrder["longitude"];
        $company->user_name = $inputOrder["user_name"];
        $company->address = $inputOrder["address"];
        $company->type = $inputOrder["type"];
        $company->payment_method = $inputOrder["payment_method"];
        $company->discount_amount =  $inputOrder["discount_amount"] > $company->subtotal_amount ? $company->subtotal_amount :  $inputOrder["discount_amount"];
        $company->status = $company->type == "pos" ? "concluded" : "accepted";

        $this->orderSafeComplementMethods($company);

        if (sizeof($extras)>0) {
            $productExtras = array();
            foreach ($product as $p) {
                foreach ($extras as $e) {
                    if ($p["product_id"] == $e["product_id"] && $p["order_item_id"] == $e["order_item_id"]) {
                        $p["extra"][] = $e;
                    }
                }
                array_push($productExtras, $p);
            }
            $company->product = $productExtras;
        }else{
            $company->product = $product;
        }
    }
    return $company;
}






























































// COMPRA E CARRINHO PELO APP DO CONSUMIDOR =====================================================================

function getCartUser($request,$response,$args){
    $token = $request->getAttribute("token");

    if (!($args['userId'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    //colocar a função de fuso horario aqui
    date_default_timezone_set($this->get_nearest_timezone($args['latitude'],$args['longitude'],$args['countryCode']));

    $sth = $this->app->db->prepare(
        "SELECT
        cart.user_id,
        company.company_id,
        company.rating,
        company.num_rating,
        company.name,
        company.photo,
        company.category_company_id,
        company.subcategory_company_id,
        company.min_purchase,
        company.delivery_fixed_fee,
        company.delivery_type_value,
        company.delivery_min_time,
        company.delivery_max_time,
        company.delivery_variable_fee,
        company.max_radius_free,
        company.max_radius,
        company.model_type,
        company.is_delivery,
        company.is_local,
        company.full_address,
        company.latitude,
        company.longitude,
        address.full_address user_address,
        address.latitude as user_latitude,
        address.longitude as user_longitude,
        (sum((product.price + (SELECT COALESCE(sum(size.price),0) FROM size where size.size_id = cart.size_id) + (SELECT COALESCE(SUM(cart_extra.quantity * extra.price),0) FROM cart_extra join extra on cart_extra.extra_id = extra.extra_id where cart_extra.cart_id = cart.cart_id))* cart.quantity)) subtotal_amount,

        (case when company.model_type = 'fixed' then (ST_Distance_Sphere(POINT(company.longitude, company.latitude), POINT(address.longitude,address.latitude))/1000) else ((case when IF(EXISTS(SELECT mobile.company_id FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL),1,0) = 1 then (SELECT ST_Distance_Sphere(POINT(mobile.longitude, mobile.latitude), POINT(address.longitude,address.latitude))/1000 FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL) else (ST_Distance_Sphere(POINT(company.longitude, company.latitude), POINT(address.longitude,address.latitude))/1000)end))end) as distance,

        IF((case when company.model_type = 'fixed' then (ST_Distance_Sphere(POINT(company.longitude, company.latitude), POINT(address.longitude,address.latitude))/1000) else ((case when IF(EXISTS(SELECT mobile.company_id FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL),1,0) = 1 then (SELECT ST_Distance_Sphere(POINT(mobile.longitude, mobile.latitude), POINT(address.longitude,address.latitude))/1000 FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL) else (ST_Distance_Sphere(POINT(company.longitude, company.latitude), POINT(address.longitude,address.latitude))/1000)end))end) < company.max_radius and company.visibility = 1 ,1,0) as is_available,

        (case when company.model_type = 'fixed' then
            (
                IF(EXISTS(SELECT hours.company_id FROM opening_hours as hours where hours.company_id = company.company_id 
                and (DAYOFWEEK(:dataon) BETWEEN hours.weekday_id 
                and hours.weekday_end_id or (hours.weekday_id = 7 
                and hours.weekday_end_id = 1 AND (DAYOFWEEK(:dataon) = 7 OR DAYOFWEEK(:dataon) = 1))) 
                AND :horaon BETWEEN if((hours.weekday_end_id > hours.weekday_id OR (hours.weekday_id = 7 
                and hours.weekday_end_id = 1 AND (DAYOFWEEK(:dataon) = 7 OR DAYOFWEEK(:dataon) = 1)))
                and DAYOFWEEK(:dataon) = hours.weekday_end_id,'00:00',hours.start_time) 
                and if((hours.weekday_end_id > hours.weekday_id OR (hours.weekday_id = 7 
                and hours.weekday_end_id = 1 AND (DAYOFWEEK(:dataon) = 7 OR DAYOFWEEK(:dataon) = 1))) 
                and DAYOFWEEK(:dataon) = hours.weekday_id,'23:59',hours.end_time)
                and TIMESTAMPDIFF(HOUR, company.last_activity, now()) < 24),1,0)
            )
            else
            (
            IF(EXISTS(SELECT mobile.company_id FROM mobile where mobile.company_id = company.company_id and(mobile.company_id = company.company_id and mobile.end_date IS NULL)),1,0)
            )
        end) AS is_online
        from cart
        join product on cart.product_id = product.product_id
        join company on company.company_id = cart.company_id
        join address ON address.user_id = :id AND address.is_selected = :selected
        where cart.user_id = :id and cart.source = 'nuppin'
        group by cart.company_id;

        SELECT 
        cart.company_id,
        product.product_id,
        cart.cart_id,
        cart.user_id,
        cart.size_id,
        cart.size_name,
        product.name,
        cart.source,
        ((product.price + COALESCE(SUM(distinct size.price),0) + COALESCE(SUM(distinct cart_extra.quantity * extra.price),0))* cart.quantity) as total_price,
        product.price,
        cart.quantity,
        cart.note,
        (case when product.is_multi_stock = 1 then (case when size.stock_quantity >= sum(distinct cart.quantity) then 1 ELSE 0 end) ELSE (case when sum(distinct cart.quantity) > product.stock_quantity AND product.is_stock = 1 then 0 ELSE 1 end) end) as is_available

        from cart
        join product on cart.product_id = product.product_id
        left join cart_extra on cart.cart_id = cart_extra.cart_id
        left join extra on cart_extra.extra_id = extra.extra_id
        left join size on cart.size_id = size.size_id
        where cart.user_id = :id and cart.source = 'nuppin'
        GROUP BY cart.cart_id
        order by cart.created_date;

        SELECT * FROM cart_extra where cart_extra.user_id = :id;

        SELECT coupon.*
        from coupon_users
        join coupon on coupon.coupon_id = coupon_users.coupon_id
        join cart on cart.user_id = :id and cart.source = 'nuppin'
        where coupon_users.user_id = :id and
        coupon.coupon_id = coupon_users.coupon_id and 
        coupon.company_id = cart.company_id and 
        coupon_users.order_id is null and 
        coupon.due_date > :data group by coupon_users.coupon_id;

        SELECT * FROM cart_info as info where info.user_id = :id;
        "
    );
    $sth->bindParam(":id", $args['userId']);     
    $sth->bindValue(":selected", "1");          
    $sth->bindValue(":data", date("Y-m-d H:i:s"));  
    $sth->bindValue(":horaon",date("H:i"));
    $sth->bindValue(":dataon",date("Y-m-d"));           
    $sth->execute();
    
    $company = $sth->fetchObject();
    $sth->nextRowset();
    $product = $sth->fetchAll();
    $sth->nextRowset();
    $extras = $sth->fetchAll();
    $sth->nextRowset();
    $coupon = $sth->fetchAll();
    $sth->nextRowset();
    $info = $sth->fetchObject();

    if($company != false){
        if (sizeof($extras)>0) {
            foreach ($product as &$p) {
                foreach ($extras as $e) {
                    if ($p["product_id"] == $e["product_id"] && $p["cart_id"] == $e["cart_id"]) {
                        $p["extra"][] = $e;
                    }
                }
            }
        }
        $company->product = $product;

        $company->info = $info;
        $this->couponCartInfo($company, $company->info->coupon_id);
        $this->logicaPrecoDelivery($company, $company->info->type);
    }else{
        $arrayJsonEmpty = array(
            "cart_company" => false,
            "cart_company_empty" => true,
        );
        return $response->withJson($arrayJsonEmpty);
    }

    if (sizeof($coupon > 0)) {
        $data = date('Y-m-d H:i');
        foreach ($coupon as $i => $item) {
            if (strtotime($data) < strtotime($coupon[$i]['due_date'])) {
                $date1 = new \DateTime($data);
                $date2 = new \DateTime($coupon[$i]['due_date']);
                $interval = $date1->diff($date2);
                $coupon[$i]['expires_day'] = $interval->d;
                $coupon[$i]['expires_hour'] = $interval->h;
                $coupon[$i]['expires_minute'] = $interval->i;
            }else{
                $coupon[$i]['expires_day'] = 0;
                $coupon[$i]['expires_hour'] = 0;
                $coupon[$i]['expires_minute'] = 0;
            }
        }
    }
    $arrayJson = array(
        "cart_company" => $company,
        "coupon" => $coupon
    );
    return $response->withJson($arrayJson);
}

function addCart($request,$response){
    $inputCart = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputCart['user_id'] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }
    
    $this->app->db->beginTransaction();
    try{

        $inputCart["source"] = "nuppin";

        $this->inserirNoCarrinho($inputCart);
        
        $this->app->db->commit();

        $this->mrGoodNews("Produto adicionado ao carrinho 🛒");

        return $response->withJson($inputCart);

    }catch(\Throwable $e){
        $this->app->db->rollBack();
        return $response->withJson(false);
    }
}

function safeWaytoPrepareOrder($order){
    $sth = $this->app->db->prepare(
        "SELECT
        cart.user_id as order_id,
        cart.user_id,
        company.company_id,
        company.name as company_name,
        info.user_name,
        info.payment_method,
        info.type,
        info.coupon_id,

        (case when info.type = 'delivery' then address.full_address else company.full_address end) address,
        (case when info.type = 'delivery' then address.latitude else company.latitude end) latitude,
        (case when info.type = 'delivery' then address.longitude else company.longitude end) longitude,

        (sum((product.price + (SELECT COALESCE(sum(size.price),0) FROM size where size.size_id = cart.size_id) + (SELECT COALESCE(SUM(cart_extra.quantity * extra.price),0) FROM cart_extra join extra on cart_extra.extra_id = extra.extra_id where cart_extra.cart_id = cart.cart_id))* cart.quantity)) subtotal_amount,

        (case when company.model_type = 'fixed' then (ST_Distance_Sphere(POINT(company.longitude, company.latitude), POINT(address.longitude,address.latitude))/1000) else ((case when IF(EXISTS(SELECT mobile.company_id FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL),1,0) = 1 then (SELECT ST_Distance_Sphere(POINT(mobile.longitude, mobile.latitude), POINT(address.longitude,address.latitude))/1000 FROM mobile where mobile.company_id = company.company_id and mobile.end_date IS NULL) else (ST_Distance_Sphere(POINT(company.longitude, company.latitude), POINT(address.longitude,address.latitude))/1000)end))end) as distance,
        cart.source

        from cart
        join product on cart.product_id = product.product_id
        join company on company.company_id = cart.company_id
        join cart_info as info on info.user_id = cart.user_id
        join address ON address.user_id = :id AND address.is_selected = :selected
        where cart.user_id = :id and cart.source = 'nuppin'
        group by cart.company_id;

        SELECT 
        cart.cart_id as order_item_id,
        cart.user_id as order_id,
        cart.product_id,
        cart.quantity,
        product.name,
        cart.size_name,
        cart.size_id,
        cart.note,
        (product.price + COALESCE(SUM(distinct size.price),0) + COALESCE(SUM(distinct cart_extra.quantity * extra.price),0)) as unit_amount,
        ((product.price + COALESCE(SUM(distinct size.price),0)+COALESCE(SUM(distinct cart_extra.quantity * extra.price),0))* cart.quantity) as total_amount
        
        from cart
        join product on cart.product_id = product.product_id
        left join cart_extra on cart.cart_id = cart_extra.cart_id
        left join extra on cart_extra.extra_id = extra.extra_id
        left join size on cart.size_id = size.size_id
        where cart.user_id = :id and cart.source = 'nuppin'
        GROUP BY cart.cart_id
        order by cart.created_date;

        SELECT 
        ce.extra_id as order_item_extra_id,
        ce.cart_id as order_item_id,
        ce.user_id as order_id, 
        ce.extra_id, 
        ce.collection_id, 
        ce.product_id,
        ce.name,
        ce.quantity,
        (SELECT extra.price FROM extra where extra.extra_id = ce.extra_id) as unit_amount,
        (SELECT sum(ce.quantity * extra.price) FROM extra where extra.extra_id = ce.extra_id) as total_amount
        FROM cart_extra as ce where ce.user_id = :id;
        "
    );
    $sth->bindParam(":id", $order["user_id"]);     
    $sth->bindValue(":selected", "1");                    
    $sth->execute();
    $company = $sth->fetchObject();
    $sth->nextRowset();
    $product = $sth->fetchAll();
    $sth->nextRowset();
    $extras = $sth->fetchAll();


    if($company != false){
        $this->couponCartInfo($company, $company->coupon_id);
        $this->orderSafeComplementMethods($company);

        if (sizeof($extras)>0) {
            $productExtras = array();
            foreach ($product as $p) {
                foreach ($extras as $e) {
                    if ($p["product_id"] == $e["product_id"] && $p["order_item_id"] == $e["order_item_id"]) {
                        $p["extra"][] = $e;
                    }
                }
                array_push($productExtras, $p);
            }
            $company->product = $productExtras;
        }else{
            $company->product = $product;
        }
    }
    
    $company->note = $order["note"];

    return $company;
}

function addOrder($request,$response){
    $inputOrder = $request->getParsedBody();
    $token = $request->getAttribute("token");

    if (!($inputOrder["user_id"] == $token["id"])) {
        return $response->withJson(false)->withStatus(403);
    }

    $this->app->db->beginTransaction();

    try{

        $safeOrder = $this->safeWaytoPrepareOrder($inputOrder);

        $arrayError = $this->verificarPedido($safeOrder);
        if ($arrayError) {
            return $response->withJson($arrayError);
        }

        $this->adicionarProdutosDoPedido($safeOrder);

        $this->decrementProductStock($safeOrder->product);

        $this->atualizarCupom($safeOrder);

        $this->send_notification($safeOrder->company_id,"Pedido: ".strtoupper($safeOrder->order_id)." - Clique e atualize o status", "Novo Pedido", $this->app->db, "all_company");

        $this->mrGoodNews("Pedido feito 🧾");

        $this->app->db->commit();

        $arrayJson = array("order" => $safeOrder);

        return $response->withJson($arrayJson);

    }catch(\Throwable $e){
        $this->app->db->rollBack();
        $error = array();
        $error['error_code'] = "001";
        $error['error_message'] = "Houve um erro ao concluir seu pedido";
        $arrayJson = array("error" => $error,"order" => false);
        return $response->withJson($arrayJson);
    }
}

}