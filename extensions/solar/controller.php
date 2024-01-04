<?php 

add_action( 'rest_api_init', function () {
    register_rest_route( 'beacon/v1', '/autocomplete/', array(
        'methods' => 'GET',
        'callback' => 'handle_autocomplete_request',
    ));
    register_rest_route( 'beacon/v1', '/estimate/', array(
        'methods' => 'GET',
        'callback' => 'fetch_solar_data',
    ));
});

function handle_autocomplete_request( $data ) {
    $input = $data->get_param( 'input' );

    $key = 'AIzaSyCyRJvH_6NF8xwcw-rfIe9w_FrVBBK5NzA';
    // Call Google Places API
    $response = wp_remote_get( "https://maps.googleapis.com/maps/api/place/autocomplete/json?input=" . urlencode($input) . "&key=" . $key );

    if ( is_wp_error( $response ) ) {
        return rest_ensure_response( $response );
    }

    $body = wp_remote_retrieve_body( $response );
    return rest_ensure_response( json_decode( $body ) );
}


function fetch_solar_data( WP_REST_Request $request ) {
    $key = 'AIzaSyCyRJvH_6NF8xwcw-rfIe9w_FrVBBK5NzA'; // Replace with your actual API key
    $lat = $request->get_param( 'lat' );
    $lng = $request->get_param( 'long' );
    $input = $request->get_param( 'input' );

    if ($lat && $lng) {
        // Latitude and Longitude are provided, use them directly
        $cords = ['lat' => $lat, 'lng' => $lng];
    } elseif ($input) {
        // Geocode the input to get coordinates
        $geocode_response = wp_remote_get( "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($input) . "&key=" . $key );
        if ( is_wp_error( $geocode_response ) ) {
            return rest_ensure_response( $geocode_response );
        }
        $geocode_body = json_decode( wp_remote_retrieve_body( $geocode_response ), true );
        $cords = $geocode_body['results'][0]['geometry']['location'] ?? null;
    } else {
        return rest_ensure_response( array( 'error' => 'No coordinates or input provided.' ) );
    }

    if ($cords) {
        $solar_endpoint = "https://solar.googleapis.com/v1/buildingInsights:findClosest?location.latitude={$cords['lat']}&location.longitude={$cords['lng']}&key={$key}";
        $solar_response = wp_remote_get( $solar_endpoint );

        if ( is_wp_error( $solar_response ) ) {
            return rest_ensure_response( $solar_response );
        }
        $solar_body = json_decode( wp_remote_retrieve_body( $solar_response ), true );
        $finance = $solar_body ?? null;

        return rest_ensure_response( $finance );
    }

    return rest_ensure_response( array( 'error' => 'No valid coordinates found.' ) );
}


function handle_autocomplete( $data ) {
    $input = $data;
    $key = 'AIzaSyCyRJvH_6NF8xwcw-rfIe9w_FrVBBK5NzA'; // Your API key

    // Call Google Places API
    $response = wp_remote_get( "https://maps.googleapis.com/maps/api/place/autocomplete/json?input=" . urlencode($input) . "&key=" . $key );

    if ( is_wp_error( $response ) ) {
        // Handle the error appropriately
        return []; // Return an empty array in case of an error
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true ); // Decode as an associative array

    // Check if 'predictions' exists in the response
    if ( isset( $data['predictions'] ) ) {
        return $data['predictions']; // Return the predictions array
    } else {
        return []; // Return an empty array if no predictions are found
    }
}


function handle_solar_data($lat, $lng, $input = null) {
    $key = 'AIzaSyCyRJvH_6NF8xwcw-rfIe9w_FrVBBK5NzA'; // Replace with your actual API key
    $cords = null;

    if ($lat && $lng) {
        // Latitude and Longitude are provided, use them directly
        $cords = ['lat' => $lat, 'lng' => $lng];
    } elseif ($input) {
        // Geocode the input to get coordinates
        $geocode_response = wp_remote_get("https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($input) . "&key=" . $key);
        if (is_wp_error($geocode_response)) {
            // Handle the error appropriately
            return ['error' => 'Geocoding failed'];
        }
        $geocode_body = json_decode(wp_remote_retrieve_body($geocode_response), true);
        $cords = $geocode_body['results'][0]['geometry']['location'] ?? null;
    }

    if ($cords) {
        $solar_endpoint = "https://solar.googleapis.com/v1/buildingInsights:findClosest?location.latitude={$cords['lat']}&location.longitude={$cords['lng']}&key={$key}";
        $solar_response = wp_remote_get($solar_endpoint);

        if (is_wp_error($solar_response)) {
            // Handle the error appropriately
            return ['error' => 'Solar API request failed'];
        }
        $solar_body = json_decode(wp_remote_retrieve_body($solar_response), true);
        return $solar_body ?? ['error' => 'No solar data found'];
    }

    return ['error' => 'No valid coordinates or input provided'];
}

function handle_solar_input($input) {
    $key = 'AIzaSyCyRJvH_6NF8xwcw-rfIe9w_FrVBBK5NzA'; // Replace with your actual API key
    $cords = null;

    if ($input) {
        // Geocode the input to get coordinates
        $geocode_response = wp_remote_get("https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($input) . "&key=" . $key);
        if (is_wp_error($geocode_response)) {
            // Handle the error appropriately
            return ['error' => 'Geocoding failed'];
        }
        $geocode_body = json_decode(wp_remote_retrieve_body($geocode_response), true);
        $cords = $geocode_body['results'][0]['geometry']['location'] ?? null;
    }

    if ($cords) {
        $solar_endpoint = "https://solar.googleapis.com/v1/buildingInsights:findClosest?location.latitude={$cords['lat']}&location.longitude={$cords['lng']}&key={$key}";
        $solar_response = wp_remote_get($solar_endpoint);

        if (is_wp_error($solar_response)) {
            // Handle the error appropriately
            return ['error' => 'Solar API request failed'];
        }
        $solar_body = json_decode(wp_remote_retrieve_body($solar_response), true);
        return $solar_body ?? ['error' => 'No solar data found'];
    }

    return ['error' => 'No valid input provided'];
}