<?php
/**
 * Enhanced Scraper Test Script
 * Demonstrates the comprehensive product data extraction capabilities
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'backend/api/enhanced_scraper.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Scraper Test - Amazon Price Tracker</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <style>
        .test-container { margin: 20px 0; }
        .json-display { background: #f5f5f5; padding: 15px; border-radius: 4px; font-family: monospace; white-space: pre-wrap; max-height: 400px; overflow-y: auto; }
        .feature-badge { display: inline-block; background: #26a69a; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px; margin: 2px; }
        .recommendation-excellent { background: #4caf50; }
        .recommendation-good { background: #ff9800; }
        .recommendation-wait { background: #f44336; }
        .rating-stars { color: #ffc107; }
        .prime-badge { background: #00a8cc; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
    </style>
</head>
<body>
    <nav class="teal">
        <div class="nav-wrapper container">
            <a href="#!" class="brand-logo">Enhanced Scraper Test</a>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col s12">
                <h4>Enhanced Amazon Product Scraper</h4>
                <p>This enhanced scraper extracts comprehensive product information similar to top competitors like Keepa, CamelCamelCamel, and Honey.</p>
            </div>
        </div>

        <!-- Test Form -->
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Test Product Scraping</span>
                        <form id="testForm">
                            <div class="row">
                                <div class="input-field col s12 m8">
                                    <input id="asin" type="text" value="B09G9BL5CP" required>
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
                                    <button class="btn waves-effect waves-light" type="submit">
                                        <i class="material-icons left">search</i>Test
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sample ASINs -->
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Sample ASINs to Test</span>
                        <div class="collection">
                            <a href="#!" class="collection-item sample-asin" data-asin="B09G9BL5CP" data-market="IN">
                                <span class="badge">iPhone</span>B09G9BL5CP (India)
                            </a>
                            <a href="#!" class="collection-item sample-asin" data-asin="B08N5WRWNW" data-market="IN">
                                <span class="badge">Echo Dot</span>B08N5WRWNW (India)
                            </a>
                            <a href="#!" class="collection-item sample-asin" data-asin="B0863TXX7V" data-market="IN">
                                <span class="badge">AirPods</span>B0863TXX7V (India)
                            </a>
                            <a href="#!" class="collection-item sample-asin" data-asin="B0BQX7KMS1" data-market="IN">
                                <span class="badge">Shoes</span>B0BQX7KMS1 (India)
                            </a>
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
                        <span class="card-title">Enhanced Product Data</span>
                        <div id="productDisplay"></div>
                        <div class="divider" style="margin: 20px 0;"></div>
                        <h6>Raw JSON Response</h6>
                        <div id="jsonDisplay" class="json-display"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div id="loading" class="row" style="display: none;">
            <div class="col s12 center">
                <div class="preloader-wrapper big active">
                    <div class="spinner-layer spinner-blue-only">
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
                <p>Scraping product data...</p>
            </div>
        </div>

        <!-- Features -->
        <div class="row">
            <div class="col s12">
                <h5>Enhanced Features</h5>
                <div class="row">
                    <div class="col s12 m6">
                        <div class="card">
                            <div class="card-content">
                                <span class="card-title">Basic Information</span>
                                <ul class="collection">
                                    <li class="collection-item">Product Title & ASIN</li>
                                    <li class="collection-item">Current & Original Price</li>
                                    <li class="collection-item">Discount Percentage</li>
                                    <li class="collection-item">Multiple Product Images</li>
                                    <li class="collection-item">Brand & Category</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col s12 m6">
                        <div class="card">
                            <div class="card-content">
                                <span class="card-title">Advanced Data</span>
                                <ul class="collection">
                                    <li class="collection-item">Customer Rating & Reviews</li>
                                    <li class="collection-item">Availability Status</li>
                                    <li class="collection-item">Prime Eligibility</li>
                                    <li class="collection-item">Seller Information</li>
                                    <li class="collection-item">Product Variants</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col s12 m6">
                        <div class="card">
                            <div class="card-content">
                                <span class="card-title">Analysis & Insights</span>
                                <ul class="collection">
                                    <li class="collection-item">Price Analysis & Trends</li>
                                    <li class="collection-item">Market Position</li>
                                    <li class="collection-item">Deal Quality Assessment</li>
                                    <li class="collection-item">Purchase Recommendation</li>
                                    <li class="collection-item">Confidence Scoring</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col s12 m6">
                        <div class="card">
                            <div class="card-content">
                                <span class="card-title">Technical Features</span>
                                <ul class="collection">
                                    <li class="collection-item">Rotating User Agents</li>
                                    <li class="collection-item">Rate Limiting & Delays</li>
                                    <li class="collection-item">Retry Logic</li>
                                    <li class="collection-item">Fallback Data Generation</li>
                                    <li class="collection-item">JSON Structured Output</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
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
                testScraper();
            });
            
            $('.sample-asin').on('click', function(e) {
                e.preventDefault();
                const asin = $(this).data('asin');
                const market = $(this).data('market');
                $('#asin').val(asin);
                $('#market').val(market);
                $('select').formSelect();
                testScraper();
            });
            
            function testScraper() {
                const asin = $('#asin').val();
                const market = $('#market').val();
                
                if (!asin) {
                    M.toast({html: 'Please enter an ASIN'});
                    return;
                }
                
                $('#loading').show();
                $('#results').hide();
                
                $.get('backend/api/enhanced_scraper.php', {
                    asin: asin,
                    market: market
                })
                .done(function(data) {
                    displayResults(data);
                })
                .fail(function(xhr) {
                    M.toast({html: 'Error: ' + (xhr.responseJSON?.error || 'Failed to scrape product')});
                })
                .always(function() {
                    $('#loading').hide();
                });
            }
            
            function displayResults(data) {
                $('#jsonDisplay').text(JSON.stringify(data, null, 2));
                
                let html = '<div class="row">';
                
                // Basic Info
                html += '<div class="col s12 m6">';
                html += '<h6><i class="material-icons left">info</i>Basic Information</h6>';
                html += '<p><strong>Title:</strong> ' + (data.title || 'N/A') + '</p>';
                html += '<p><strong>ASIN:</strong> ' + (data.asin || 'N/A') + '</p>';
                html += '<p><strong>Brand:</strong> ' + (data.brand || 'N/A') + '</p>';
                html += '<p><strong>Price:</strong> ₹' + (data.price || 'N/A') + '</p>';
                if (data.original_price) {
                    html += '<p><strong>Original Price:</strong> ₹' + data.original_price + '</p>';
                }
                if (data.discount) {
                    html += '<p><strong>Discount:</strong> <span class="feature-badge">' + data.discount + '% OFF</span></p>';
                }
                html += '</div>';
                
                // Ratings & Reviews
                html += '<div class="col s12 m6">';
                html += '<h6><i class="material-icons left">star</i>Ratings & Reviews</h6>';
                if (data.rating) {
                    html += '<p><strong>Rating:</strong> <span class="rating-stars">';
                    for (let i = 1; i <= 5; i++) {
                        html += i <= data.rating ? '★' : '☆';
                    }
                    html += '</span> ' + data.rating + '/5</p>';
                }
                if (data.review_count) {
                    html += '<p><strong>Reviews:</strong> ' + data.review_count.toLocaleString() + '</p>';
                }
                html += '<p><strong>Availability:</strong> ' + (data.availability || 'Unknown') + '</p>';
                if (data.prime_eligible) {
                    html += '<p><strong>Prime:</strong> <span class="prime-badge">Prime Eligible</span></p>';
                }
                html += '</div>';
                
                html += '</div><div class="row">';
                
                // Recommendation
                if (data.recommendation) {
                    html += '<div class="col s12">';
                    html += '<h6><i class="material-icons left">thumb_up</i>Recommendation</h6>';
                    html += '<div class="card-panel ' + getRecommendationClass(data.recommendation.level) + ' white-text">';
                    html += '<h6>' + data.recommendation.level + ' - ' + data.recommendation.action + '</h6>';
                    html += '<p>' + data.recommendation.message + '</p>';
                    html += '<p><strong>Confidence:</strong> ' + data.recommendation.confidence + '%</p>';
                    if (data.recommendation.factors) {
                        html += '<p><strong>Factors:</strong></p><ul>';
                        data.recommendation.factors.forEach(factor => {
                            html += '<li>' + factor + '</li>';
                        });
                        html += '</ul>';
                    }
                    html += '</div>';
                    html += '</div>';
                }
                
                html += '</div>';
                
                // Features
                if (data.features && data.features.length > 0) {
                    html += '<div class="row"><div class="col s12">';
                    html += '<h6><i class="material-icons left">list</i>Key Features</h6>';
                    html += '<ul class="collection">';
                    data.features.slice(0, 5).forEach(feature => {
                        html += '<li class="collection-item">' + feature + '</li>';
                    });
                    html += '</ul>';
                    html += '</div></div>';
                }
                
                $('#productDisplay').html(html);
                $('#results').show();
            }
            
            function getRecommendationClass(level) {
                if (level.includes('Highly') || level.includes('Recommended')) return 'green';
                if (level.includes('Consider') || level.includes('Maybe')) return 'orange';
                return 'red';
            }
        });
    </script>
</body>
</html>