<?php
function paginate($conn, $table, $current_page, $records_per_page, $filters = [])
{
    // Calculate total records with optional filters
    $filter_sql = "1=1";
    foreach ($filters as $key => $value) {
        $filter_sql .= " AND $key LIKE :$key";
    }

    $total_records_query = $conn->prepare("SELECT COUNT(*) FROM $table WHERE $filter_sql");
    foreach ($filters as $key => $value) {
        $total_records_query->bindValue(":$key", "%$value%");
    }
    $total_records_query->execute();
    $total_records = $total_records_query->fetchColumn();

    // Pagination calculations
    $total_pages = ceil($total_records / $records_per_page);
    $offset = ($current_page - 1) * $records_per_page;

    // Fetch paginated data
    $sql = "SELECT * FROM $table WHERE $filter_sql LIMIT :offset, :records_per_page";
    $stmt = $conn->prepare($sql);
    foreach ($filters as $key => $value) {
        $stmt->bindValue(":$key", "%$value%");
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':records_per_page', $records_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'data' => $data,
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'records_per_page' => $records_per_page,
        'total_records' => $total_records,
    ];
}

function renderPaginationControls($current_page, $total_pages, $records_per_page, $query_params = [])
{
    // Add existing query params to the pagination links
    $query_params['records_per_page'] = $records_per_page;
    ?>
    <div class="pagination-control">
        <a href="?<?= http_build_query(array_merge($query_params, ['page' => 1])) ?>" class="btn btn-secondary btn-sm <?= $current_page == 1 ? 'disabled' : '' ?>">First</a>
        <a href="?<?= http_build_query(array_merge($query_params, ['page' => $current_page - 1])) ?>" class="btn btn-secondary btn-sm <?= $current_page == 1 ? 'disabled' : '' ?>">Previous</a>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?<?= http_build_query(array_merge($query_params, ['page' => $i])) ?>" class="btn btn-primary btn-sm <?= $i == $current_page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <a href="?<?= http_build_query(array_merge($query_params, ['page' => $current_page + 1])) ?>" class="btn btn-secondary btn-sm <?= $current_page == $total_pages ? 'disabled' : '' ?>">Next</a>
        <a href="?<?= http_build_query(array_merge($query_params, ['page' => $total_pages])) ?>" class="btn btn-secondary btn-sm <?= $current_page == $total_pages ? 'disabled' : '' ?>">Last</a>
    </div>
    <?php
}
?>
