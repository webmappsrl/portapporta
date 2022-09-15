
<template>
    <div id="container">
        <div :id="mapRef" class="wm-map"></div>
    </div>
</template>

<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova'
import "leaflet/dist/leaflet.css";
import L from "leaflet";
const DEFAULT_TILES = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
const DEFAULT_ATTRIBUTION = '<a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery (c) <a href="https://www.mapbox.com/">Mapbox</a>';
const DEFAULT_CENTER = [42, 12];
export default {
    name: "Map",
    mixins: [FormField, HandlesValidationErrors],
    props: ['field', 'edit'],
    data() { return { mapRef: `mapContainer-${Math.floor(Math.random() * 10000 + 10)}` } },
    methods: {
        initMap() {
            setTimeout(() => {
                if (this.field.latlng !== undefined && this.field.latlng.length != 0) {
                    var center = this.field.latlng;
                } else if (this.field.center !== undefined && this.field.latlng.center != 0) {
                    var center = this.field.center;
                } else {
                    var center = DEFAULT_CENTER;
                }
                const mapDiv = L.map(this.mapRef).setView(center, 13);
                const myZoom = {
                    start: mapDiv.getZoom(),
                    end: mapDiv.getZoom()
                };

                L.tileLayer(
                    this.field.tiles ?? DEFAULT_TILES,
                    {
                        attribution: this.field.attribution ?? DEFAULT_ATTRIBUTION,
                        maxZoom: 15,
                        minZoom: 8,
                        id: "mapbox/streets-v11",
                    }
                ).addTo(mapDiv);
                var circleOption = {
                    color: 'red',
                    fillColor: '#f03',
                    fillOpacity: 1,
                    radius: 100
                };
                var circle = L.circle(center, circleOption).addTo(mapDiv);
                if (this.edit) {
                    mapDiv.on('click', (e) => {
                        const currentRadius = circle.getRadius();
                        mapDiv.removeLayer(circle);
                        circle = new L.circle(e.latlng, { ...circleOption, ...{ radius: currentRadius } }).addTo(mapDiv);
                        this.$emit('latlng', [e.latlng.lat, e.latlng.lng]);
                    });
                    mapDiv.on('zoomstart', function () {
                        myZoom.start = mapDiv.getZoom();
                    });
                    mapDiv.on('zoomend', function () {
                        myZoom.end = mapDiv.getZoom();
                        var diff = myZoom.start - myZoom.end;
                        if (diff > 0) {
                            circle.setRadius(circle.getRadius() * 2);
                        } else if (diff < 0) {
                            circle.setRadius(circle.getRadius() / 2);
                        }
                    });
                } else {
                    mapDiv.dragging.disable();
                    mapDiv.zoomControl.remove()
                    mapDiv.scrollWheelZoom.disable();
                    mapDiv.doubleClickZoom.disable();
                }
            }, 300);

        }
    },
    mounted() {
        this.initMap();
    },
};
</script>
