<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Real-Time Tracking Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .chart-container {
      position: relative;
      height: 300px;
      width: 100%;
      margin: 0 auto;
    }
  </style>
</head>
<body>
  <?php include 'navbar.php'; ?>

  <div class="container mt-5">
    <h2>Real-Time Tracking Dashboard</h2>
    <p class="text-muted">Auto-refreshing with AJAX every 10 seconds.</p>

    <div class="row">
      <div class="col-md-6">
        <div class="card mb-3">
          <div class="card-header">Service Users (SUs)</div>
          <div class="card-body">
            <div class="chart-container">
              <canvas id="suChart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card mb-3">
          <div class="card-header">Incidents (Open vs. Closed)</div>
          <div class="card-body">
            <div class="chart-container">
              <canvas id="incidentsChart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="card mb-3">
          <div class="card-header">Complaints</div>
          <div class="card-body">
            <div class="chart-container">
              <canvas id="complaintsChart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card mb-3">
          <div class="card-header">Safeguarding</div>
          <div class="card-body">
            <div class="chart-container">
              <canvas id="safeguardingChart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="card mb-3">
          <div class="card-header">Moveout & Documents</div>
          <div class="card-body">
            <div class="chart-container">
              <canvas id="otherChart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    let suChart, incidentsChart, complaintsChart, safeguardingChart, otherChart;

    function initCharts() {
      suChart = new Chart(document.getElementById('suChart').getContext('2d'), {
  type: 'doughnut',
  data: {
    labels: ['Males', 'Females', 'Minors', 'Adults'],
    datasets: [{
      data: [0, 0, 0, 0],
      backgroundColor: ['#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0']
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    onClick: (evt, item) => {
      if (!item.length) return;
      const idx = item[0].index;
      switch (idx) {
        case 0: // Males
          window.location.href = 'sudata.php?filter_gender=Male';
          break;
        case 1: // Females
          window.location.href = 'sudata.php?filter_gender=Female';
          break;
        case 2: // Minors
          window.location.href = 'sudata.php?filter_age=Minor';
          break;
        case 3: // Adults
          window.location.href = 'sudata.php?filter_age=Adult';
          break;
        default:
          break;
      }
    }
  }
});

      incidentsChart = new Chart(document.getElementById('incidentsChart').getContext('2d'), {
  type: 'bar',
  data: {
    labels: ['Open Incidents', 'Closed Incidents'],
    datasets: [{
      label: 'Incidents',
      data: [0, 0],
      backgroundColor: ['rgba(255, 99, 132, 0.6)', 'rgba(54, 162, 235, 0.6)']
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    onClick: (evt, item) => {
      if (!item.length) return;
      const idx = item[0].index;
      if (idx === 0) {
        window.location.href = 'manage_incident_reports.php?filter_status=Open';
      } else {
        window.location.href = 'manage_incident_reports.php?filter_status=Close';
      }
    }
  }
});

      complaintsChart = new Chart(document.getElementById('complaintsChart').getContext('2d'), {
        type: 'bar',
        data: {
          labels: ['Open Complaints', 'Closed Complaints'],
          datasets: [{
            label: 'Complaints',
            data: [0, 0],
            backgroundColor: ['rgba(255, 159, 64, 0.6)', 'rgba(75, 192, 192, 0.6)']
          }]
        },
        options: {
        responsive: true,
        maintainAspectRatio: false,
        onClick: (evt, item) => {
          if (!item.length) return;
          const idx = item[0].index;
          if (idx === 0) {
            window.location.href = 'manage_complaints.php?filter_status=Open';
          } else {
            window.location.href = 'manage_complaints.php?filter_status=Close';
          }
        }
      }
      });

      safeguardingChart = new Chart(document.getElementById('safeguardingChart').getContext('2d'), {
        type: 'pie',
        data: {
          labels: ['Referrals', 'Vulnerable SUs'],
          datasets: [{
            data: [0, 0],
            backgroundColor: ['#FF6384', '#36A2EB']
          }]
        },
        options: { responsive: true, maintainAspectRatio: false }
      });

      otherChart = new Chart(document.getElementById('otherChart').getContext('2d'), {
        type: 'bar',
        data: {
          labels: ['Moveout', 'Documents'],
          datasets: [{
            label: 'Count',
            data: [0, 0],
            backgroundColor: ['#FFCE56', '#4BC0C0']
          }]
        },
        options: { responsive: true, maintainAspectRatio: false }
      });
    }

    function fetchData() {
      fetch('dashboard_data.php')
        .then(response => response.json())
        .then(data => {
          suChart.data.datasets[0].data = [data.males, data.females, data.minors, data.adults];
          suChart.update();

          incidentsChart.data.datasets[0].data = [data.open_incidents, data.closed_incidents];
          incidentsChart.update();

          complaintsChart.data.datasets[0].data = [data.open_complaints, data.closed_complaints];
          complaintsChart.update();

          safeguardingChart.data.datasets[0].data = [data.total_referrals, data.total_vulnerable];
          safeguardingChart.update();

          otherChart.data.datasets[0].data = [data.total_moveout, data.total_docs];
          otherChart.update();
        })
        .catch(error => console.error('Error fetching data:', error));
    }

    document.addEventListener('DOMContentLoaded', () => {
      initCharts();
      fetchData();
      setInterval(fetchData, 10000);
    });
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
