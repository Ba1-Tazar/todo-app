SELECT
    us.id AS user_id,
    us.username,
    ta.id AS task_id,
    ta.title AS task_name,
    case 
        when ta.is_done = 1 then 'Yes'
        when ta.is_done = 0 then 'No'
    end AS `Completed_?`,
    ta.description AS description,
    ta.created_at AS creation_date
FROM users us
JOIN tasks ta ON us.id = ta.user_id
ORDER BY user_id, task_name
;