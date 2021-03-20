<?php
/**
 * News service:
 * Handles storing and retrieval of news
 */

 class NewsService{
     public function getAllNews($excludeUnpublished=false){        
        $app = \Slim\Slim::getInstance();
        $db = connect_db();
        $sql ="
            SELECT id, title, body, publishedAt, unpublishedAt 
            FROM news
        ";
        if($excludeUnpublished)
            $sql .= " WHERE unpublishedAt IS NULL";

	    $result = $db->query( $sql );
		$data = array();
	    while ($row = $result->fetch_array(MYSQLI_ASSOC) ) {
	      $data[] = $row;
	    }
		return $data;
       }

    public function addNewsItem($title, $body){
        $publishedAt = date("Y-m-d H:i:s");
        $this->updateOrAddNewsItem($title, $body, $publishedAt);
    }

    public function unpublishItem($id){
        $unpublishedAt = date("Y-m-d H:i:s");
        $db = connect_db();
        $sql = "UPDATE news SET unpublishedAt=? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("si", $unpublishedAt, $id);
        $stmt->execute();
    }
    
    public function republishItem($id){
        $publishedAt = date("Y-m-d H:i:s");
        $db = connect_db();
        $sql = "UPDATE news SET unpublishedAt=null, publishedAt=? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("si", $publishedAt, $id);
        $stmt->execute();
    }

    private function updateOrAddNewsItem($title, $body, $publishedAt, $currentId=null){
        $db = connect_db();
        $sql = "REPLACE INTO news (id, title, body, publishedAt) values(?,?,?,?)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("isss", $currentId, $title, $body, $publishedAt);
        $stmt->execute();
    }
 }
?>