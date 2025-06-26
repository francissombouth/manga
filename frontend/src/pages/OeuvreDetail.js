import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import {
  Container,
  Grid,
  Typography,
  Box,
  Chip,
  Button,
  Card,
  CardContent,
  Divider,
  List,
  ListItem,
  ListItemText,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Alert,
} from '@mui/material';
import axios from 'axios';
import { useAuth } from '../contexts/AuthContext';

function OeuvreDetail() {
  const { id } = useParams();
  const { isAuthenticated } = useAuth();
  const [oeuvre, setOeuvre] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [openDialog, setOpenDialog] = useState(false);
  const [selectedCollection, setSelectedCollection] = useState('');
  const [collections, setCollections] = useState([]);
  const [successMessage, setSuccessMessage] = useState('');

  useEffect(() => {
    const fetchOeuvre = async () => {
      try {
        const response = await axios.get(`/oeuvres/${id}`);
        setOeuvre(response.data);
      } catch (error) {
        setError('Erreur lors du chargement de l\'œuvre');
      } finally {
        setLoading(false);
      }
    };

    fetchOeuvre();
  }, [id]);

  const handleOpenDialog = async () => {
    if (isAuthenticated) {
      try {
        const response = await axios.get('/collections');
        setCollections(response.data['hydra:member']);
        setOpenDialog(true);
      } catch (error) {
        setError('Erreur lors du chargement des collections');
      }
    }
  };

  const handleCloseDialog = () => {
    setOpenDialog(false);
    setSelectedCollection('');
  };

  const handleAddToCollection = async () => {
    try {
      await axios.post('/collections', {
        oeuvre: `/oeuvres/${id}`,
        collection: selectedCollection,
      });
      setSuccessMessage('Œuvre ajoutée à la collection avec succès');
      handleCloseDialog();
    } catch (error) {
      setError('Erreur lors de l\'ajout à la collection');
    }
  };

  if (loading) {
    return (
      <Container>
        <Typography>Chargement...</Typography>
      </Container>
    );
  }

  if (error) {
    return (
      <Container>
        <Typography color="error">{error}</Typography>
      </Container>
    );
  }

  if (!oeuvre) {
    return (
      <Container>
        <Typography>Œuvre non trouvée</Typography>
      </Container>
    );
  }

  return (
    <Container>
      <Box sx={{ my: 4 }}>
        <Grid container spacing={4}>
          <Grid item xs={12} md={4}>
            <img
              src={oeuvre.image || '/placeholder.jpg'}
              alt={oeuvre.titre}
              style={{ width: '100%', height: 'auto' }}
            />
          </Grid>
          <Grid item xs={12} md={8}>
            <Typography variant="h4" component="h1" gutterBottom>
              {oeuvre.titre}
            </Typography>
            <Typography variant="h6" color="text.secondary" gutterBottom>
              {oeuvre.auteur.nom} {oeuvre.auteur.prenom}
            </Typography>
            <Box sx={{ my: 2 }}>
              {oeuvre.tags.map((tag) => (
                <Chip
                  key={tag.id}
                  label={tag.nom}
                  sx={{ mr: 1, mb: 1 }}
                />
              ))}
            </Box>
            <Typography variant="body1" paragraph>
              {oeuvre.description}
            </Typography>
            {isAuthenticated && (
              <Button
                variant="contained"
                color="primary"
                onClick={handleOpenDialog}
                sx={{ mt: 2 }}
              >
                Ajouter à une collection
              </Button>
            )}
          </Grid>
        </Grid>

        <Divider sx={{ my: 4 }} />

        <Typography variant="h5" gutterBottom>
          Chapitres
        </Typography>
        <List>
          {oeuvre.chapitres.map((chapitre) => (
            <Card key={chapitre.id} sx={{ mb: 2 }}>
              <CardContent>
                <Typography variant="h6">
                  {chapitre.titre}
                </Typography>
                <Typography variant="body2" color="text.secondary">
                  {chapitre.contenu}
                </Typography>
              </CardContent>
            </Card>
          ))}
        </List>

        <Dialog open={openDialog} onClose={handleCloseDialog}>
          <DialogTitle>Ajouter à une collection</DialogTitle>
          <DialogContent>
            <FormControl fullWidth sx={{ mt: 2 }}>
              <InputLabel>Collection</InputLabel>
              <Select
                value={selectedCollection}
                onChange={(e) => setSelectedCollection(e.target.value)}
                label="Collection"
              >
                {collections.map((collection) => (
                  <MenuItem key={collection.id} value={collection.id}>
                    {collection.nom}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
          </DialogContent>
          <DialogActions>
            <Button onClick={handleCloseDialog}>Annuler</Button>
            <Button
              onClick={handleAddToCollection}
              variant="contained"
              disabled={!selectedCollection}
            >
              Ajouter
            </Button>
          </DialogActions>
        </Dialog>

        {successMessage && (
          <Alert
            severity="success"
            onClose={() => setSuccessMessage('')}
            sx={{ mt: 2 }}
          >
            {successMessage}
          </Alert>
        )}
      </Box>
    </Container>
  );
}

export default OeuvreDetail; 