function calculateBMI(event) {
    event.preventDefault();

    // Collecting input values
    const weight = parseFloat(document.getElementById('weight').value);
    const height = parseFloat(document.getElementById('height').value);
    const name = document.getElementById('name').value;
    const gender = document.querySelector('input[name="gender"]:checked').value;

    // Calculating BMI
    const bmiHeight = height / 100;
    const bmi = weight / (bmiHeight * bmiHeight);

    // Round BMI to 2 decimal places
    const roundedBMI = parseFloat(bmi.toFixed(2));

    // Determining BMI category
    let category = '';
    if (bmi < 18.5) {
        category = 'Kurus';
    } else if (bmi >= 18.5 && bmi < 24.9) {
        category = 'Normal';
    } else if (bmi >= 25 && bmi < 29.9) {
        category = 'Berlebihan Berat Badan';
    } else {
        category = 'Obesiti';
    }

    // Displaying the result
    document.getElementById('result').innerHTML = `${name} (${gender}), BMI anda adalah ${bmi.toFixed(2)} (${category})`;

    // Sending data to the backend
    fetch('./index.php?page=bmi', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            name: name,       
            height: height,   
            weight: weight,   
            gender: gender,   
            bmi: roundedBMI, // Use the rounded value
            category: category 
        })
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                alert('BMI data saved successfully!');
            } else {
                alert('Error saving BMI data: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error saving BMI data. Please try again.');
        });
}
