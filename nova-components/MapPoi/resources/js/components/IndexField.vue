<template>
    <span>{{ field.street }}</span>
</template>

<script>
import axios from 'axios'
export default {
    props: ['resourceName', 'field', 'street'],
    methods: {

    },
    mounted() {
        // if (this.field.position != null) {
        //     console.log('ciao');
        //     const lat = this.field.position[1];
        //     const lon = this.field.position[0];
        //     axios.get(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json`)
        //         .then(response => {
        //             if(response != null && response.data != null && response.data.address != null && response.data.address['road']) {
        //                 this.field.street = response.data.address['road']
        //             }
        //         });
        // }
    }
}
</script>
