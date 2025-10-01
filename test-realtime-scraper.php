<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real-time Amazon Scraper Test</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .test-result { margin: 20px 0; padding: 15px; border-radius: 4px; }
        .success { background: #c8e6c9; border-left: 4px solid #4caf50; }
        .error { background: #ffcdd2; border-left: 4px solid #f44336; }
        .info { background: #e1f5fe; border-left: 4px solid #03a9f4; }
        .json-display { background: #f5f5f5; padding: 10px; border-radius: 4px; font-family: monospace; white-space: pre-wrap; max-height: 400px; overflow-y: auto; }
        .price-comparison { display: flex; gap: 20px; margin: 10px 0; }
        .price-box { padding: 10px; border-radius: 4px; text-align: center; min-width: 120px; }
        .current-price { background: #4caf50; color: white; }
        .old-price { background: #ff9800; color: white; }
        .price-change { background: #2196f3; color: white; }
    </style>
</head>
<body>
    <nav class="teal">
        <div class="nav-wrapper container">
            <a href="#!" class="brand-logo">Real-time Scraper Test</a>
            <ul class="right">
                <li><a href="/">Back to Tracker</a></li>
            </ul>
        </div>
    </nav>

    <div class="container" style="margin-top: 20px;">
        <div class="row">
            <div class="col s12">
                <h4>Real-time Amazon Price Scraper Test</h4>
                <p>This tool tests the real-time scraper that loads Amazon pages internally to get the most current prices.</p>
            </div>
        </div>

        <!-- Test Form -->
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Test Real-time Scraping</span>
                        <form id="testForm">
                            <div class="row">
                                <div class="input-field col s12 m8">
                                    <input id="asin" type="text" value="B0BQHS5P9R" required>
                                    <label for="asin">Amazon ASIN</label>
                                </div>
                                <div class="input-field col s12 m2">
                                    <select id="market">
                                        <option value="IN" selected>India</option>
                                        <option value="US">US</option>
                                        <option value="UK">UK</option>
                                    </select>
                                    <label>Market</label>
                                </div>
                                <div class="input-field col s12 m2">
                                    <button class="btn waves-effect waves-light teal" type="submit">
                                        <i class="material-icons left">refresh</i>Test Scrape
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Test Buttons -->
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Quick Tests</span>
                        <div class="row">
                            <div class="col s12 m3">
                                <button class="btn-small waves-effect waves-light blue quick-test" data-asin="B0BQHS5P9R" data-market="IN">
                                    Skechers Shoes (IN)
                                </button>
                            </div>
                            <div class="col s12 m3">
                                <button class="btn-small waves-effect waves-light blue quick-test" data-asin="B09G9BL5CP" data-market="IN">
                                    iPhone 14 (IN)
                                </button>
                            </div>
                            <div class="col s12 m3">
                                <button class="btn-small waves-effect waves-light blue quick-test" data-asin="B08N5WRWNW" data-market="IN">
                                    Echo Dot (IN)
                                </button>
                            </div>
                            <div class="col s12 m3">
                                <button class="btn-small waves-effect waves-light blue quick-test" data-asin="B0863TXX7V" data-market="IN">
                                    AirPods (IN)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results -->
        <div id="results" class="row" style="display: none;">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Scraping Results</span>
                        <div id="resultContent"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div id="loading" class="row" style="display: none;">
            <div class="col s12 center">
                <div class="preloader-wrapper big active">
                    <div class="spinner-layer spinner-teal-only">
                        <div class="circle-clipper left">
                            <div class="circle"></div>
                        </div>
                        <div class="gap-patch">
                            <div class="circle"></div>
                        </div>
                        <div class="circle-clipper right">
                            <div class="circle"></div>
                        </div>
                    </div>
                </div>
                <p>Scraping real-time data from Amazon...</p>
                <p><small>This may take 10-30 seconds for accurate results</small></p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        $(document).ready(function() {
            $('select').formSelect();
            
            $('#testForm').on('submit', function(e) {
                e.preventDefault();
                testScraper($('#asin').val(), $('#market').val());
            });
            
            $('.quick-test').on('click', function() {
                const asin = $(this).data('asin');
                const market = $(this).data('market');
                $('#asin').val(asin);
                $('#market').val(market);
                $('select').formSelect();
                testScraper(asin, market);
            });
            
            function testScraper(asin, market) {
                if (!asin) {
                    M.toast({html: 'Please enter an ASIN'});
                    return;
                }
                
                $('#loading').show();
                $('#results').hide();
                
                const startTime = Date.now();
                
                $.get('backend/api/realtime_scraper.php', {
                    asin: asin,
                    market: market
                })
                .done(function(data) {
                    const endTime = Date.now();
                    const duration = ((endTime - startTime) / 1000).toFixed(2);
                    displayResults(data, duration, true);
                })
                .fail(function(xhr) {
                    const endTime = Date.now();
                    const duration = ((endTime - startTime) / 1000).toFixed(2);
                    const error = xhr.responseJSON || {error: 'Request failed'};
                    displayResults(error, duration, false);
                })
                .always(function() {
                    $('#loading').hide();
                });
            }
            
            function displayResults(data, duration, success) {
                let html = '';
                
                if (success && data.price) {
                    html += '<div class="test-result success">';
                    html += '<h5><i class="material-icons left">check_circle</i>Scraping Successful</h5>';
                    html += '<p><strong>Duration:</strong> ' + duration + ' seconds</p>';
                    html += '<p><strong>Method:</strong> ' + (data.method || 'realtime') + '</p>';
                    
                    html += '<div class="price-comparison">';
                    html += '<div class="price-box current-price">';
                    html += '<div><strong>Current Price</strong></div>';
                    html += '<div style="font-size: 1.5em;">₹' + (data.price ? data.price.toLocaleString() : 'N/A') + '</div>';
                    html += '</div>';
                    
                    if (data.original_price) {
                        html += '<div class="price-box old-price">';
                        html += '<div><strong>Original Price</strong></div>';
                        html += '<div style="font-size: 1.2em;">₹' + data.original_price.toLocaleString() + '</div>';
                        html += '</div>';
                    }
                    
                    if (data.discount) {
                        html += '<div class="price-box price-change">';
                        html += '<div><strong>Discount</strong></div>';
                        html += '<div style="font-size: 1.2em;">' + data.discount + '% OFF</div>';
                        html += '</div>';
                    }
                    html += '</div>';
                    
                    if (data.title) {
                        html += '<p><strong>Product:</strong> ' + data.title + '</p>';
                    }
                    
                    if (data.rating) {
                        html += '<p><strong>Rating:</strong> ' + data.rating + '/5';
                        if (data.review_count) {
                            html += ' (' + data.review_count.toLocaleString() + ' reviews)';
                        }
                        html += '</p>';
                    }
                    
                    if (data.availability) {
                        html += '<p><strong>Availability:</strong> ' + data.availability + '</p>';
                    }
                    
                    html += '</div>';
                } else {
                    html += '<div class="test-result error">';
                    html += '<h5><i class="material-icons left">error</i>Scraping Failed</h5>';
                    html += '<p><strong>Duration:</strong> ' + duration + ' seconds</p>';
                    html += '<p><strong>Error:</strong> ' + (data.error || 'Unknown error occurred') + '</p>';
                    html += '</div>';
                }
                
                html += '<div class="test-result info">';
                html += '<h6>Raw Response Data:</h6>';
                html += '<div class="json-display">' + JSON.stringify(data, null, 2) + '</div>';
                html += '</div>';
                
                $('#resultContent').html(html);
                $('#results').show();
            }
        });
    </script>
</body>
</html>