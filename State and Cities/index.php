<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Registration</title>
</head>
<body>
    <h2>Employee Registration</h2>
    <form method="post" action="save_custom_entry.php">
        Name: <input type="text" name="name"><br><br>
        Reg No: <input type="text" name="regno"><br><br>
        Age: <input type="text" name="age"><br><br>

        Country:
        <select id="country" name="country">
            <option value="">Select Country</option>
        </select>
        <div id="countryContainer"></div>
        <br><br>

        <div id="stateSection">
            State:
            <select id="state" name="state">
                <option value="">Select State</option>
            </select>
            <div id="stateContainer"></div>
        </div>
        <br>

        <div id="citySection">
            City:
            <select id="city" name="city">
                <option value="">Select City</option>
            </select>
            <div id="cityContainer"></div>
        </div>
        <br><br>

        <input type="submit" value="Submit">
    </form>

    <script>
        const countrySelect = document.getElementById('country');
        const stateSelect = document.getElementById('state');
        const citySelect = document.getElementById('city');
        const stateContainer = document.getElementById('stateContainer');
        const cityContainer = document.getElementById('cityContainer');
        const countryContainer = document.getElementById('countryContainer');
        const stateSection = document.getElementById('stateSection');
        const citySection = document.getElementById('citySection');

        let data = {};

        fetch('statesandcities.json')
            .then(response => response.json())
            .then(json => {
                data = json;
                populateCountries();
            });

        function populateCountries() {
            for (let country in data) {
                const option = document.createElement('option');
                option.value = country;
                option.textContent = country;
                countrySelect.appendChild(option);
            }

            const other = document.createElement('option');
            other.value = 'Other';
            other.textContent = 'Other';
            countrySelect.appendChild(other);
        }

        function updateStates(country) {
            stateSelect.innerHTML = '<option value="">Select State</option>';
            citySelect.innerHTML = '<option value="">Select City</option>';
            stateContainer.innerHTML = '';
            cityContainer.innerHTML = '';

            if (country === 'Other') {
                // Hide dropdowns and show input boxes
                countryContainer.innerHTML = '<input type="text" name="newCountry" placeholder="Enter New Country">';
                stateContainer.innerHTML = '<input type="text" name="newState" placeholder="Enter New State">';
                cityContainer.innerHTML = '<input type="text" name="newCity" placeholder="Enter New City">';
                stateSelect.style.display = 'none';
                citySelect.style.display = 'none';
            } else {
                // Show dropdowns and reset input boxes
                countryContainer.innerHTML = '';
                stateSelect.style.display = 'inline';
                citySelect.style.display = 'inline';
                stateContainer.innerHTML = '';
                cityContainer.innerHTML = '';

                if (data[country]) {
                    const states = Object.keys(data[country]);
                    states.forEach(state => {
                        const option = document.createElement('option');
                        option.value = state;
                        option.textContent = state;
                        stateSelect.appendChild(option);
                    });

                    const other = document.createElement('option');
                    other.value = 'Other';
                    other.textContent = 'Other';
                    stateSelect.appendChild(other);
                }
            }
        }

        function updateCities(country, state) {
            citySelect.innerHTML = '<option value="">Select City</option>';
            cityContainer.innerHTML = '';

            if (state === 'Other') {
                stateContainer.innerHTML = '<input type="text" name="newState" placeholder="Enter New State">';
                cityContainer.innerHTML = '<input type="text" name="newCity" placeholder="Enter New City">';
            } else if (data[country] && data[country][state]) {
                data[country][state].forEach(city => {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
                    citySelect.appendChild(option);
                });

                const other = document.createElement('option');
                other.value = 'Other';
                other.textContent = 'Other';
                citySelect.appendChild(other);
            }
        }

        countrySelect.addEventListener('change', function () {
            const selectedCountry = this.value;
            updateStates(selectedCountry);
        });

        stateSelect.addEventListener('change', function () {
            const selectedState = this.value;
            const selectedCountry = countrySelect.value;
            updateCities(selectedCountry, selectedState);
        });

        citySelect.addEventListener('change', function () {
            if (this.value === 'Other') {
                cityContainer.innerHTML = '<input type="text" name="newCity" placeholder="Enter New City">';
            } else {
                cityContainer.innerHTML = '';
            }
        });
    </script>
</body>
</html>
