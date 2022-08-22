
<template>
    <div id="container">
        <div id="mapContainer" class="wm-map"></div>
    </div>
</template>
 
<script>
import { FormField, HandlesValidationErrors, DependentFormField } from 'laravel-nova'
import "leaflet/dist/leaflet.css";
import L from "leaflet";

export default {
    name: "Map",
    mixins: [FormField, HandlesValidationErrors, DependentFormField],
    props: ['field', 'edit'],
    data() {
        return {
            center: [this.field.position[1], this.field.position[0]]
        }
    },
    methods: {
        initMap: function () {
            const mapDiv = L.map("mapContainer").setView(this.center, 13);
            L.tileLayer(
                'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                {
                    attribution:
                        'Map data (c) <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery (c) <a href="https://www.mapbox.com/">Mapbox</a>',
                    maxZoom: 18,
                    id: "mapbox/streets-v11",
                }
            ).addTo(mapDiv);
            const circle = L.circle(this.center, {
                color: 'red',
                fillColor: '#f03',
                fillOpacity: 1,
                radius: 50
            }).addTo(mapDiv);
            console.log(this.edit)
            if (this.edit) {
                mapDiv.on('click', this.onMapClick);
            }
        },
        onMapClick(e) {
            alert("You clicked the map at " + e.latlng);
        }
    },
    mounted() {
        this.initMap();
    },
};
</script>
