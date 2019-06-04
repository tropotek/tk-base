<?php



try {
    $config = \App\Config::getInstance();
    $db = $config->getDb();

    $subjectInfo = $db->getTableInfo('subject');
    if (!in_array('publish', $subjectInfo)) {
        $sql = sprintf('alter table subject add publish TINYINT(1) default 1 not null after date_end;');
        $db->exec($sql);
    }
    if (!in_array('notify', $subjectInfo)) {
        $sql = sprintf('alter table subject add notify TINYINT(1) default 1 not null after date_end;');
        $db->exec($sql);
    }
//    if (!in_array('email', $subjectInfo)) {
//        $sql = sprintf('alter table subject add email VARCHAR(255) default \'\' not null after code;');
//        $db->exec($sql);
//    }

} catch (\Tk\Db\Exception $e) {
}




